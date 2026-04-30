<?php

namespace App\Services\Vision;

use App\Models\Project;
use App\Support\ReelForgeStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Structured visual analysis of the first reference product image for card generation (FLUX kontext).
 * Used to enrich the English prompt with colors, background, angle, materials — before user_card_kontext.
 */
class ProductCardPhotoAnalysisService
{
    /**
     * @return array<string, mixed>|null Decoded JSON or null if unavailable
     */
    public function analyze(Project $project): ?array
    {
        $first = $project->images()->orderBy('order')->first();
        if ($first === null) {
            return null;
        }

        $disk = ReelForgeStorage::contentDisk();
        if (! Storage::disk($disk)->exists($first->path)) {
            return null;
        }

        $bytes = Storage::disk($disk)->get($first->path);
        $ext = pathinfo($first->path, PATHINFO_EXTENSION);

        return $this->analyzeRawBytes($bytes, is_string($ext) ? $ext : 'jpg', 'photo_analysis_system');
    }

    /**
     * Vision JSON for arbitrary image bytes (e.g. Replicate output before text overlay).
     *
     * @param  string  $promptConfigKey  Key on merged prompts config (e.g. card_overlay_analysis_system).
     * @return array<string, mixed>|null
     */
    public function analyzeRawBytes(string $bytes, string $fileExtension = 'jpg', string $promptConfigKey = 'card_overlay_analysis_system'): ?array
    {
        if ($bytes === '') {
            return null;
        }

        $system = trim((string) config('prompts.'.$promptConfigKey, ''));
        if ($system === '') {
            return null;
        }

        $mime = $this->guessMime('x.'.$fileExtension);
        $b64 = base64_encode($bytes);

        if (! empty(config('services.anthropic.api_key'))) {
            $out = $this->analyzeWithAnthropic($b64, $mime, $system);
            if ($out !== null) {
                return $out;
            }
        }

        $allowOpenAi = (bool) config('platform.photo_guided.card_photo_analysis.openai_fallback', true);
        if ($allowOpenAi && ! empty(config('services.openai.api_key'))) {
            return $this->analyzeWithOpenAI($b64, $mime, $system);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function analyzeWithAnthropic(string $base64, string $mediaType, string $system): ?array
    {
        $model = (string) config('services.anthropic.vision_model', 'claude-3-5-sonnet-20241022');
        $key = (string) config('services.anthropic.api_key');

        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'x-api-key' => $key,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 1024,
                    'system' => $system,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => $mediaType,
                                        'data' => $base64,
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'Analyze this product reference image. Return only the JSON object specified in the system prompt.',
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('ProductCardPhotoAnalysisService: Anthropic error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = $response->json('content.0.text');

            return is_string($text) ? $this->parseJsonObject($text) : null;
        } catch (\Throwable $e) {
            Log::warning('ProductCardPhotoAnalysisService: Anthropic exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function analyzeWithOpenAI(string $base64, string $mediaType, string $system): ?array
    {
        $key = (string) config('services.openai.api_key');
        $model = (string) config('services.openai.vision_model', 'gpt-4o-mini');
        $uri = 'data:'.$mediaType.';base64,'.$base64;

        try {
            $response = Http::withToken($key)
                ->timeout(90)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'max_tokens' => 800,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $system,
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analyze this product image and return only the JSON object described in the system message.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => $uri],
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('ProductCardPhotoAnalysisService: OpenAI error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $content = $response->json('choices.0.message.content');

            return is_string($content) ? $this->parseJsonObject($content) : null;
        } catch (\Throwable $e) {
            Log::warning('ProductCardPhotoAnalysisService: OpenAI exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseJsonObject(string $text): ?array
    {
        $t = trim($text);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```/u', $t, $m)) {
            $t = trim($m[1]);
        }

        $decoded = json_decode($t, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function guessMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
