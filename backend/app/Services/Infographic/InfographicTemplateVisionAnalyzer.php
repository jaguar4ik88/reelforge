<?php

namespace App\Services\Infographic;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calls OpenAI Vision with the infographic template prompt (from tmp/exemple/template/code).
 * Returns decoded JSON: canvas, background, layers — no ReelForge-specific shaping here.
 */
class InfographicTemplateVisionAnalyzer
{
    public function analyse(string $absoluteImagePath, string $templateLabel): ?array
    {
        $key = config('services.openai.api_key');
        if ($key === null || $key === '') {
            Log::warning('InfographicTemplateVisionAnalyzer: missing OPENAI_API_KEY');

            return null;
        }

        $model = (string) config('services.openai.vision_model', 'gpt-4o-mini');
        $raw = file_get_contents($absoluteImagePath);
        if ($raw === false) {
            return null;
        }
        $base64 = base64_encode($raw);
        $mime = mime_content_type($absoluteImagePath) ?: 'image/jpeg';

        $prompt = $this->buildPrompt($templateLabel);

        try {
            $response = Http::withToken($key)
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'max_tokens' => 4096,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a canvas template analyser. You analyse product card / marketplace layout images and return precise JSON describing all visual layers. Return ONLY valid JSON, no explanation, no markdown.',
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:'.$mime.';base64,'.$base64,
                                        'detail' => 'high',
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Infographic OpenAI Vision error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $content = $response->json('choices.0.message.content');
            $decoded = json_decode((string) $content, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                Log::error('Infographic Vision invalid JSON', ['content' => $content]);

                return null;
            }

            return $decoded;
        } catch (\Throwable $e) {
            Log::error('InfographicTemplateVisionAnalyzer exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function buildPrompt(string $templateName): string
    {
        return <<<PROMPT
Analyse this product card image and return a JSON canvas template.

Template name: "{$templateName}"

RULES:
- x, y, width, height are FRACTIONS of canvas size (0.0 to 1.0), not pixels
- Detect ALL visible elements: background shapes, text blocks, image area, decorative elements
- Every text block must have editable: true
- Product photo placeholder must be type "image" with editable: true
- Badge group (numbered circle + text pill together) = type "badge_group"
- Non-editable decorative elements (blurred circles, bg shapes) = editable: false
- Colors as hex strings
- fontSize as integer (approximate, relative to a 1000px-wide reference canvas)

Return this exact structure:

{
  "canvas": {
    "width": 1000,
    "height": 1000
  },
  "background": {
    "type": "solid | gradient",
    "color": "#hex (if solid)",
    "gradient": {
      "from": "#hex",
      "to": "#hex",
      "direction": "135deg"
    }
  },
  "layers": [
    {
      "id": "unique_snake_case_id",
      "type": "rect | text | image | badge_group",
      "label": "Human readable Russian or Ukrainian label (e.g. Заголовок, Фото товара)",
      "editable": true,
      "x": 0.0,
      "y": 0.0,
      "width": 0.0,
      "height": 0.0,
      "style": {
        "fontSize": 48,
        "fontWeight": "bold | 500 | normal",
        "color": "#hex (for text layers)",
        "background": "#hex (for rect/badge layers)",
        "borderRadius": 0,
        "textAlign": "center | left | right",
        "opacity": 1
      },
      "placeholder": "Text shown before user edits (for text layers)",
      "children": []
    }
  ]
}

For badge_group layers, children array must contain numbered circle, pill, and text as in a product card.

Identify elements in this order:
1. Background decorative shapes (editable: false)
2. Product image area (type: image, editable: true)
3. Title / name badge (type: badge_group or rect+text, editable: true)
4. Feature badges 1, 2, 3... (type: badge_group, editable: true)
5. Any other text elements
PROMPT;
    }
}
