<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * GPT Image 1.5 — product card from product photo + layout reference (OpenAI Images Edits API).
 */
class GptImageProductCardService
{
    public function generateProductCard(
        string $prompt,
        string $productBytes,
        string $productMime,
        string $exampleBytes,
        string $exampleMime,
        string $size,
    ): string {
        $key = config('services.openai.api_key');
        if ($key === null || $key === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $model = (string) config('services.openai.gpt_image_model', 'gpt-image-1.5');
        $quality = (string) config('services.openai.gpt_image_quality', 'medium');
        $fidelity = (string) config('services.openai.gpt_image_input_fidelity', 'high');

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
            'quality' => $quality,
            'input_fidelity' => $fidelity,
            'output_format' => 'png',
            'images' => [
                ['image_url' => $this->dataUrl($productBytes, $productMime)],
                ['image_url' => $this->dataUrl($exampleBytes, $exampleMime)],
            ],
        ];

        $response = Http::withToken($key)
            ->timeout(600)
            ->connectTimeout(60)
            ->post('https://api.openai.com/v1/images/edits', $payload);

        if ($response->failed()) {
            Log::error('OpenAI images/edits failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('OpenAI image generation failed: HTTP '.$response->status());
        }

        $b64 = $response->json('data.0.b64_json');
        if (! is_string($b64) || $b64 === '') {
            throw new RuntimeException('OpenAI returned no image data.');
        }

        $raw = base64_decode($b64, true);
        if ($raw === false || $raw === '') {
            throw new RuntimeException('OpenAI image base64 decode failed.');
        }

        return $raw;
    }

    private function dataUrl(string $bytes, string $mime): string
    {
        $mime = $this->normalizeMime($mime);

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    private function normalizeMime(string $mime): string
    {
        $mime = strtolower(trim($mime));
        if ($mime === 'image/jpg') {
            return 'image/jpeg';
        }
        if ($mime === '' || ! str_starts_with($mime, 'image/')) {
            return 'image/jpeg';
        }

        return $mime;
    }
}
