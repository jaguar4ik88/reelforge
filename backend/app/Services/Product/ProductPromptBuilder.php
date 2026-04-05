<?php

namespace App\Services\Product;

/**
 * Builds the final text prompt from structured settings + enriched wishes (English).
 * Scene/content/suffix strings live in config/prompts.php.
 */
class ProductPromptBuilder
{
    public function build(
        string $contentType,
        string $sceneStyle,
        string $enrichedWishes,
        string $rawWishes,
        string $imageCaption = '',
        ?int $videoDurationSeconds = null,
    ): string {
        $parts = [
            'Task: generate marketing visuals based on the uploaded product reference image.',
        ];

        if ($contentType === 'video' && $videoDurationSeconds !== null) {
            $key = $videoDurationSeconds >= 20 ? 'video_long' : 'video_short';
            $parts[] = (string) config("prompts.content_types.{$key}", config('prompts.content_types.video'));
        } else {
            $parts[] = (string) config("prompts.content_types.{$contentType}", config('prompts.content_types.photo'));
        }

        if ($contentType === 'photo') {
            $style = (string) config("prompts.styles.{$sceneStyle}", '');
            if ($style !== '') {
                $parts[] = 'Scene style: '.$style;
            }
        }

        $caption = trim($imageCaption);
        if ($caption !== '') {
            $parts[] = 'Reference image description (from analysis): '.$caption;
        }

        $raw = trim($rawWishes);
        $enriched = trim($enrichedWishes);

        if ($contentType === 'card') {
            if ($enriched !== '') {
                $parts[] = 'Design direction (English): '.$enriched;
            }
            $parts[] = str_replace('{card_text}', $rawWishes, (string) config('prompts.content_types.card'));
        } elseif ($contentType === 'video') {
            if ($enriched !== '') {
                $parts[] = 'Video direction (English): '.$enriched;
            } elseif ($raw !== '') {
                $parts[] = 'Video direction (original): '.$raw;
            }
        } else {
            if ($sceneStyle === 'from_wishes') {
                if ($enriched !== '') {
                    $parts[] = 'Primary art direction (English): '.$enriched;
                } elseif ($raw !== '') {
                    $parts[] = 'User wishes (original): '.$raw;
                }
            } else {
                if ($enriched !== '') {
                    $parts[] = 'Additional direction (English): '.$enriched;
                } elseif ($raw !== '') {
                    $parts[] = 'Additional direction (original): '.$raw;
                }
            }
        }

        if ($contentType === 'video' && $videoDurationSeconds !== null && $videoDurationSeconds > 0) {
            $parts[] = 'Target video duration: '.$videoDurationSeconds.' seconds.';
        }

        $parts[] = (string) config('prompts.suffix', '8k resolution, photorealistic');

        return implode("\n\n", array_values(array_filter($parts, fn ($p) => trim((string) $p) !== '')));
    }
}
