<?php

namespace App\Services\Vision;

use App\Models\Project;
use App\Support\ReelForgeStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Writes a marketplace-style product description from reference images (vision).
 * OpenAI when configured; otherwise stub from title + product_meta qualities.
 */
class ProductListingDescriptionService
{
    /** Max length stored on projects.description */
    private const DESCRIPTION_MAX = 8000;

    public function generate(Project $project, string $localePrefix): string
    {
        $project->loadMissing('images');
        $rows = $project->images()->orderBy('order')->get();
        if ($rows->isEmpty()) {
            throw new RuntimeException('No product images to analyze.');
        }

        $disk = ReelForgeStorage::contentDisk();
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            return $this->stubDescription($project);
        }

        $imageParts = [];
        foreach ($rows->take(5) as $img) {
            if (! Storage::disk($disk)->exists($img->path)) {
                continue;
            }
            $bytes = Storage::disk($disk)->get($img->path);
            $mime = $this->guessMime($img->path);
            $dataUri = 'data:'.$mime.';base64,'.base64_encode($bytes);
            $imageParts[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $dataUri],
            ];
        }

        if ($imageParts === []) {
            return $this->stubDescription($project);
        }

        $useUk = str_starts_with(strtolower($localePrefix), 'uk');
        $languageRule = $useUk
            ? 'Write the ENTIRE description in Ukrainian (marketing tone for marketplaces — Ozon, Prom, Rozetka style). No English unless it is the real brand name on the packaging.'
            : 'Write the ENTIRE description in English (marketing tone for ecommerce listings).';

        $count = count($imageParts);
        $multi = $count > 1
            ? "You see {$count} photos of ONE product — merge details into one coherent listing."
            : 'You see one product photo.';

        $textPrompt = <<<PROMPT
You are an e‑commerce copywriter. {$multi}

{$languageRule}

Return ONLY valid JSON:
{
  "description": "single string: 3–6 short paragraphs OR clear sections separated by blank lines — materials, sizing/fit cues if inferable, use cases, what makes this product appealing. Plain text inside the JSON string (no HTML, no markdown headings). Aim for roughly 450–950 characters unless the item is extremely simple."
}

Do NOT repeat the literal JSON skeleton. Fill "description" with real persuasive copy inferred from what's visible.

PROMPT;

        try {
            $content = array_merge(
                [['type' => 'text', 'text' => $textPrompt]],
                $imageParts
            );
            $model = config('services.openai.vision_model', 'gpt-4o-mini');

            $response = Http::withToken($apiKey)
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'max_tokens' => 900,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $content,
                        ],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('ProductListingDescriptionService: HTTP failed', ['error' => $e->getMessage()]);

            return $this->stubDescription($project);
        }

        if ($response->failed()) {
            Log::warning('ProductListingDescriptionService: OpenAI error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->stubDescription($project);
        }

        $rawJson = $response->json('choices.0.message.content');
        if (! is_string($rawJson) || $rawJson === '') {
            return $this->stubDescription($project);
        }

        $decoded = json_decode($rawJson, true);
        $text = is_array($decoded) && isset($decoded['description']) ? trim((string) $decoded['description']) : '';
        if ($text === '') {
            return $this->stubDescription($project);
        }

        return $this->truncate($text);
    }

    private function stubDescription(Project $project): string
    {
        $meta = $project->product_meta_json;
        $name = is_array($meta) && isset($meta['name']) ? trim((string) $meta['name']) : $project->title;
        $qualities = is_array($meta) && isset($meta['qualities']) && is_array($meta['qualities']) ? $meta['qualities'] : [];
        $bulletText = implode('. ', array_map(static fn ($q) => trim((string) $q), array_filter($qualities, static fn ($q) => is_string($q) && trim($q) !== '')));

        if ($bulletText !== '') {
            $paragraph = "{$name}. {$bulletText}.";
        } else {
            $paragraph = $name ?: __('messages.photo_guided.default_title');
        }

        return $this->truncate($paragraph.' '.__('messages.photo_guided.description_stub_suffix'));
    }

    private function truncate(string $text): string
    {
        if (mb_strlen($text) <= self::DESCRIPTION_MAX) {
            return $text;
        }

        return mb_substr($text, 0, self::DESCRIPTION_MAX);
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
