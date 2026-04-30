<?php

namespace App\Services\Product;

/**
 * Builds the final text prompt from structured settings + enriched wishes (English).
 * Scene/content/suffix strings: config/prompts/photo.php, card.php, video.php, common.php.
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
     * Background-only prompt for PHP+(Imagick|GD) card composition (Replicate T2I, no product in frame).
     *
     * @param  ?bool  $productIsLight  luma(товар) выше порога: светлый товар → T2I тёмный фон; тёмный товар → светлый фон
     */
    public function buildCardBackgroundPromptForComposite(
        string $enrichedWishes,
        string $imageCaption = '',
        ?bool $productIsLight = null
    ): string {
        $enriched = trim($enrichedWishes);
        $caption = trim($imageCaption);
        $instr = (string) config('prompts.background_composite.instruction', '');
        $noProduct = 'No product, no object, no item to sell in the frame. Pure backdrop only, empty product placement area. No letters, no numbers, no typography, no text, no watermark, no logos.';

        $contrast = null;
        if ($productIsLight === true) {
            $contrast = 'Background contrast: reference product is relatively LIGHT — use a DARK, moody empty set (charcoal, deep blue-gray, near-black) with soft gradients. Keep the lower area visually clear for a product placement.';
        } elseif ($productIsLight === false) {
            $contrast = 'Background contrast: reference product is relatively DARK — use a BRIGHT, airy empty set (off-white, pale cream, light gray) and soft even light. Keep the lower area visually clear for a product placement.';
        }

        $parts = array_filter(
            array_merge(
                $enriched !== '' ? ['Scene and mood: '.$enriched] : [],
                $caption !== '' ? ['Reference lighting and palette (for background only, do not add the product as an object): '.$caption] : [],
                $instr !== '' ? ['Layout instruction: '.$instr] : [],
                $contrast !== null ? [$contrast] : [],
                [$noProduct, 'Negative: product photography subject, mannequin, shoes on surface, hand, people.'],
            ),
            static fn (string $p) => $p !== ''
        );

        return implode(' ', $parts);
    }

    /**
     * English prompt for FLUX Kontext: full-frame product card scene (before PHP typography).
     *
     * @param  array<string, mixed>|null  $referenceAnalysis  JSON from {@see ProductCardPhotoAnalysisService::analyze}
     */
    public function buildCardSceneRegenPrompt(
        string $enrichedWishes,
        string $imageCaption = '',
        ?array $referenceAnalysis = null
    ): string {
        $base = trim((string) config('prompts.card_scene_regen_instruction', ''));
        $enriched = trim($enrichedWishes);
        $caption = trim($imageCaption);
        $subject = '';
        if (is_array($referenceAnalysis) && isset($referenceAnalysis['card_output_subject']) && is_string($referenceAnalysis['card_output_subject'])) {
            $subject = trim($referenceAnalysis['card_output_subject']);
        }

        $parts = array_filter([
            $base !== '' ? $base : null,
            $subject !== '' ? 'Reference intent: '.$subject : null,
            $caption !== '' ? 'Additional notes: '.$caption : null,
            $enriched !== '' ? 'Creative direction: '.$enriched : null,
        ], static fn (?string $p) => $p !== null && $p !== '');

        $prefix = trim((string) config('prompts.card_scene_regen_watermark_prefix', ''));
        $suffix = trim((string) config('prompts.card_scene_regen_watermark_suffix', ''));
        $body = implode("\n\n", $parts);

        return implode("\n\n", array_filter([$prefix, $body, $suffix], static fn (string $s) => $s !== ''));
    }

    private function buildCardPrompt(string $enrichedWishes, string $rawWishes, string $imageCaption = ''): string
    {
        $enriched = trim($enrichedWishes);
        $raw = trim($rawWishes);
        $contentTypeLine = str_replace('{card_text}', $rawWishes, (string) config('prompts.content_types.card'));
        $suffixCard = trim((string) config('prompts.suffix_card', ''));
        $caption = trim($imageCaption);

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
