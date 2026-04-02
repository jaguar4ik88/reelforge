<?php

namespace App\Services\Product;

/**
 * Builds the final text prompt from structured settings (kept in sync with frontend buildProductGenerationPrompt.js).
 */
class ProductPromptBuilder
{
    private const CONTENT_TYPE = [
        'photo' => 'Output: photorealistic product imagery suitable for ads and social.',
        'card'  => 'Output: a clean e-commerce product card layout with strong typography hierarchy.',
        'video' => 'Output: vertical short-form video storyboard / motion concept for the product.',
    ];

    private const SCENE_STYLE = [
        'in_use' => 'Scene: product in real use — hands or context showing application; authentic lifestyle feel.',
        'environment' => 'Scene: product placed in a believable real-world environment; natural light, depth, context.',
        'studio' => 'Scene: catalog / studio shot — isolated product on neutral background, soft even lighting, sharp detail.',
    ];

    public function build(string $contentType, string $sceneStyle, string $userWishes, string $imageCaption = ''): string
    {
        $parts = [
            'Task: generate marketing visuals based on the uploaded product reference image.',
            self::CONTENT_TYPE[$contentType] ?? self::CONTENT_TYPE['photo'],
            self::SCENE_STYLE[$sceneStyle] ?? self::SCENE_STYLE['studio'],
        ];

        $caption = trim($imageCaption);
        if ($caption !== '') {
            $parts[] = 'Reference image description (from analysis): '.$caption;
        }

        $wishes = trim($userWishes);
        if ($wishes !== '') {
            $parts[] = 'Additional user direction: '.$wishes;
        }

        return implode("\n\n", $parts);
    }
}
