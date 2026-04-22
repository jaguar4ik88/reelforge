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
        if ($contentType === 'card') {
            return $this->buildCardPrompt($enrichedWishes, $rawWishes, $imageCaption);
        }

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

        if ($contentType === 'video') {
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

    /**
     * Card mode: English enrichment + product-card content template + card suffix (kontext-friendly).
     */
    private function buildCardPrompt(string $enrichedWishes, string $rawWishes, string $imageCaption = ''): string
    {
        $enriched        = trim($enrichedWishes);
        $raw             = trim($rawWishes);
        $contentTypeLine = str_replace('{card_text}', $rawWishes, (string) config('prompts.content_types.card'));
        $suffixCard      = trim((string) config('prompts.suffix_card', ''));
        $caption         = trim($imageCaption);

        // Order matches kontext spec: enriched (includes photo analysis when present) → content template → suffix_card
        $step1 = $enriched !== '' ? $enriched : ($caption !== '' ? 'Reference image description (from vision): '.$caption : '');

        $segments = array_filter([
            $step1,
            trim($contentTypeLine),
            $suffixCard,
        ], fn ($s) => is_string($s) && $s !== '');

        return implode('. ', $segments);
    }
}
