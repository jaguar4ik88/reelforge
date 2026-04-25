<?php

namespace App\Services\Product;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Translates and expands user wishes (any language) into English for FLUX / Replicate.
 * Prefers Anthropic Claude; falls back to OpenAI; then plain text with a visual-brief prefix.
 */
class WishesPromptEnrichmentService
{
    public function enrich(
        string $productName,
        string $category,
        string $contentType,
        string $sceneStyle,
        string $rawWishes,
        ?string $photoAnalysisJson = null,
        ?string $cardTextsJson = null,
    ): string {
        if ($contentType === 'card' && $photoAnalysisJson !== null && $photoAnalysisJson !== '') {
            return $this->enrichCardKontext(
                $productName,
                $category,
                $rawWishes,
                $photoAnalysisJson,
                $cardTextsJson ?? '[]',
            );
        }

        if (! filter_var(config('platform.photo_guided.wishes_enrichment', false), FILTER_VALIDATE_BOOLEAN)) {
            return '';
        }

        $wishes = trim($rawWishes);

        if ($wishes === '') {
            return $this->enrichEmpty($productName, $category, $contentType, $sceneStyle);
        }

        if ($this->shouldSkipApi($wishes)) {
            if ($contentType === 'card') {
                return '';
            }

            return $wishes;
        }

        $system = (string) config('prompts.wishes_processor.system', '');
        [$userTemplateKey, $replacements] = $this->resolveUserTemplate($contentType, $productName, $category, $wishes);
        $user = str_replace(
            array_keys($replacements),
            array_values($replacements),
            (string) config("prompts.wishes_processor.{$userTemplateKey}", '')
        );

        if ($system === '' || $user === '') {
            return $this->fallbackPlain($wishes);
        }

        $text = $this->callAnthropic($system, $user);
        if ($text !== null) {
            return $text;
        }

        $text = $this->callOpenAI($system, $user);
        if ($text !== null) {
            return $text;
        }

        return $this->fallbackPlain($wishes);
    }

    /**
     * Card + reference photo: English layout prompt using analysis JSON + verbatim Cyrillic labels.
     * Runs when photo analysis is present, independent of APP_PLATFORM_WISHES_ENRICHMENT.
     */
    private function enrichCardKontext(
        string $productName,
        string $category,
        string $rawWishes,
        string $photoAnalysisJson,
        string $cardTextsJson,
    ): string {
        $template = (string) config('prompts.wishes_processor.user_card_kontext', '');
        if ($template === '') {
            return '';
        }

        $user = str_replace(
            [
                '{product_name}',
                '{category}',
                '{photo_analysis}',
                '{card_texts_json}',
            ],
            [
                $productName,
                $category,
                $photoAnalysisJson,
                $cardTextsJson,
            ],
            $template
        );

        $system = (string) config('prompts.wishes_processor.system', '');

        $text = $this->callAnthropic($system, $user);
        if ($text !== null) {
            return $text;
        }

        $text = $this->callOpenAI($system, $user);
        if ($text !== null) {
            return $text;
        }

        return $this->fallbackCardKontext($rawWishes, $photoAnalysisJson);
    }

    private function fallbackCardKontext(string $rawWishes, string $photoAnalysisJson): string
    {
        return 'Commercial product card: graphic background, solo product (no hands from reference). Analysis: '.$photoAnalysisJson
            .'. On-card text verbatim: '.trim($rawWishes);
    }

    /**
     * Short pure-ASCII input — skip LLM to save tokens (still English-friendly for FLUX).
     */
    private function shouldSkipApi(string $wishes): bool
    {
        if (preg_match('/[^\x00-\x7F]/', $wishes)) {
            return false;
        }

        return strlen($wishes) < 120;
    }

    private function enrichEmpty(string $productName, string $category, string $contentType, string $sceneStyle): string
    {
        if ($contentType !== 'photo' || $sceneStyle !== 'from_wishes') {
            return '';
        }

        $template = (string) config('prompts.wishes_processor.user_no_wishes', '');
        if ($template === '') {
            return '';
        }

        $user = str_replace(
            ['{product_name}', '{category}'],
            [$productName, $category],
            $template
        );
        $system = (string) config('prompts.wishes_processor.system', '');

        $text = $this->callAnthropic($system, $user) ?? $this->callOpenAI($system, $user);

        return $text ?? '';
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function resolveUserTemplate(string $contentType, string $productName, string $category, string $wishes): array
    {
        $key = match ($contentType) {
            'card' => 'user_card',
            'video' => 'user_video',
            default => 'user',
        };

        return [
            $key,
            [
                '{product_name}' => $productName,
                '{category}'     => $category,
                '{wishes}'       => $wishes,
            ],
        ];
    }

    private function fallbackPlain(string $wishes): string
    {
        return 'Visual brief (user input may be multilingual — interpret for photorealistic product shot): '.$wishes;
    }

    private function callAnthropic(string $system, string $user): ?string
    {
        $key = config('services.anthropic.api_key');
        if (empty($key)) {
            return null;
        }

        $model = (string) config('services.anthropic.model', 'claude-sonnet-4-20250514');

        try {
            $response = Http::timeout(90)
                ->withHeaders([
                    'x-api-key'         => $key,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'       => $model,
                    'max_tokens'  => 400,
                    'system'      => $system,
                    'messages'    => [
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('WishesPromptEnrichmentService: Anthropic HTTP error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $text = $data['content'][0]['text'] ?? null;

            return is_string($text) ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('WishesPromptEnrichmentService: Anthropic exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function callOpenAI(string $system, string $user): ?string
    {
        $key = config('services.openai.api_key');
        if (empty($key)) {
            return null;
        }

        $model = (string) config('services.openai.wishes_model', 'gpt-4o-mini');

        try {
            $response = Http::timeout(90)
                ->withToken($key)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => $model,
                    'max_tokens'  => 400,
                    'temperature' => 0.4,
                    'messages'    => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('WishesPromptEnrichmentService: OpenAI HTTP error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $text = $response->json('choices.0.message.content');

            return is_string($text) ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('WishesPromptEnrichmentService: OpenAI exception', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
