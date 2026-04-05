<?php

namespace App\Services\Vision;

use App\Models\Project;
use App\Support\ReelForgeStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Analyzes the first product reference image and returns structured metadata (name, category, qualities).
 * Uses OpenAI Vision when OPENAI_API_KEY is set; otherwise returns a safe stub.
 */
class ProductPhotoAnalysisService
{
    private const ALLOWED_CATEGORIES = [
        'apparel', 'electronics', 'home', 'beauty', 'food', 'sports', 'other',
    ];

    /**
     * @return array{name: string, category: string, qualities: string[]}
     */
    public function analyze(Project $project): array
    {
        $first = $project->images()->orderBy('order')->first();
        if ($first === null) {
            throw new RuntimeException('No product images to analyze.');
        }

        $disk = ReelForgeStorage::contentDisk();
        if (! Storage::disk($disk)->exists($first->path)) {
            throw new RuntimeException('Image file not found.');
        }

        $bytes    = Storage::disk($disk)->get($first->path);
        $mime     = $this->guessMime($first->path);
        $dataUri  = 'data:'.$mime.';base64,'.base64_encode($bytes);
        $apiKey   = config('services.openai.api_key');

        if (empty($apiKey)) {
            return $this->stubResult($project);
        }

        $model = config('services.openai.vision_model', 'gpt-4o-mini');
        $cats  = implode(', ', self::ALLOWED_CATEGORIES);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'             => $model,
                    'max_tokens'        => 500,
                    'response_format'   => ['type' => 'json_object'],
                    'messages'          => [
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => <<<PROMPT
You are an e-commerce catalog assistant. Look at the product photo and return ONLY valid JSON with this shape (no markdown):
{
  "name": "short product title in the same language as visible text on packaging or English if none",
  "category": "one of: {$cats}",
  "qualities": ["2-4 short selling points in Russian or Ukrainian"]
}
"name" must describe the actual product in the image. "qualities" are marketing-style bullets (style, materials, comfort, etc.).
PROMPT,
                                ],
                                [
                                    'type'      => 'image_url',
                                    'image_url' => ['url' => $dataUri],
                                ],
                            ],
                        ],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('ProductPhotoAnalysisService: HTTP failed', ['error' => $e->getMessage()]);

            return $this->stubResult($project);
        }

        if ($response->failed()) {
            Log::warning('ProductPhotoAnalysisService: OpenAI error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return $this->stubResult($project);
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || $content === '') {
            return $this->stubResult($project);
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return $this->stubResult($project);
        }

        return $this->normalize($decoded, $project);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{name: string, category: string, qualities: string[]}
     */
    private function normalize(array $raw, Project $project): array
    {
        $name = isset($raw['name']) ? trim((string) $raw['name']) : '';
        if ($name === '') {
            $name = $project->title ?: 'Product';
        }

        $cat = isset($raw['category']) ? strtolower(trim((string) $raw['category'])) : 'other';
        if (! in_array($cat, self::ALLOWED_CATEGORIES, true)) {
            $cat = 'other';
        }

        $qualities = [];
        if (isset($raw['qualities']) && is_array($raw['qualities'])) {
            foreach ($raw['qualities'] as $q) {
                $s = trim((string) $q);
                if ($s !== '' && count($qualities) < 6) {
                    $qualities[] = $s;
                }
            }
        }
        if ($qualities === []) {
            $qualities = ['Якісне зображення товару', 'Деталі видно чітко'];
        }

        return [
            'name'       => mb_substr($name, 0, 200),
            'category'   => $cat,
            'qualities'  => array_slice($qualities, 0, 4),
        ];
    }

    /**
     * @return array{name: string, category: string, qualities: string[]}
     */
    private function stubResult(Project $project): array
    {
        return [
            'name'      => $project->title ?: __('messages.photo_guided.default_title'),
            'category'  => 'other',
            'qualities' => [
                __('messages.photo_guided.stub_quality_1'),
                __('messages.photo_guided.stub_quality_2'),
            ],
        ];
    }

    private function guessMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            default       => 'image/jpeg',
        };
    }
}
