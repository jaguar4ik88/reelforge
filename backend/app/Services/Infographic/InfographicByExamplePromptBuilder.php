<?php

namespace App\Services\Infographic;

use RuntimeException;

/**
 * GPT Image 1.5 prompt for “card by example” — template from config/prompts/card/example.php.
 */
class InfographicByExamplePromptBuilder
{
    /**
     * @param  array<string, mixed>  $productAnalysis
     * @param  array<string, mixed>  $exampleAnalysis
     * @param  list<string>  $featureLines
     */
    public function build(
        array $productAnalysis,
        array $exampleAnalysis,
        string $title,
        array $featureLines,
        string $aspectRatioLabel,
        string $canvasPixelLabel,
        string $openAiApiSize,
    ): string {
        $title = trim($title);
        if ($title === '') {
            $title = 'Product';
        }

        /** @var array<string, mixed> $gpt */
        $gpt = config('prompts.card_by_example.gpt_image', []);
        $template = (string) ($gpt['template'] ?? '');
        if ($template === '') {
            throw new RuntimeException('Config prompts.card_by_example.gpt_image.template is missing or empty.');
        }

        $needsBgRemoval = filter_var($productAnalysis['needs_background_removal'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $bgRemovalNote = $needsBgRemoval
            ? (string) ($gpt['product_note_needs_bg_removal'] ?? '')
            : (string) ($gpt['product_note_default'] ?? '');

        $background = $this->str($exampleAnalysis['background'] ?? '');
        $backgroundColors = $this->formatHexList($exampleAnalysis['background_colors'] ?? []);
        $backgroundLine = $background !== '' || $backgroundColors !== '[]'
            ? trim("{$background}".($backgroundColors !== '[]' ? " (reference colors: {$backgroundColors})" : ''))
            : 'Clean commercial card background consistent with Image 2.';

        $productPosition = $this->str($exampleAnalysis['product_position'] ?? 'centered, prominent');
        $titlePosition = $this->str($exampleAnalysis['title_position'] ?? 'top');
        $titleStyle = $this->str($exampleAnalysis['title_style'] ?? 'bold sans-serif');
        $featuresPosition = $this->str($exampleAnalysis['features_position'] ?? 'below title or lower third');
        $featuresLayout = $this->str($exampleAnalysis['features_layout'] ?? 'list');
        $colorPalette = $this->formatHexList($exampleAnalysis['color_palette'] ?? []);
        $overallStyle = $this->str($exampleAnalysis['overall_style'] ?? 'minimal');
        $compositionNotes = $this->str($exampleAnalysis['composition_notes'] ?? '');

        $titleColorHint = $this->primaryTypographyColorHint($exampleAnalysis);
        $bodyColorHint = $this->secondaryTypographyColorHint($exampleAnalysis);

        $featuresBlock = $this->bulletFeatureLines($featureLines);

        $canvasLine = "{$canvasPixelLabel} pixels, {$aspectRatioLabel} aspect ratio (output size {$openAiApiSize})";

        $map = [
            '{bg_removal_note}' => $bgRemovalNote,
            '{canvas_line}' => $canvasLine,
            '{background_line}' => $backgroundLine,
            '{product_position}' => $productPosition,
            '{title}' => $title,
            '{title_color_hint}' => $titleColorHint,
            '{title_position}' => $titlePosition,
            '{title_style}' => $titleStyle,
            '{features_layout}' => $featuresLayout,
            '{features_position}' => $featuresPosition,
            '{features_block}' => $featuresBlock,
            '{body_color_hint}' => $bodyColorHint,
            '{overall_style}' => $overallStyle,
            '{composition_notes}' => $compositionNotes,
            '{color_palette}' => $colorPalette,
        ];

        return strtr($template, $map);
    }

    /**
     * @param  list<string>  $lines
     */
    private function bulletFeatureLines(array $lines): string
    {
        $out = [];
        foreach ($lines as $line) {
            $t = trim((string) $line);
            if ($t !== '') {
                $out[] = '  • '.$t;
            }
        }

        return $out === [] ? '  • —' : implode("\n", $out);
    }

    /**
     * @param  array<string, mixed>  $exampleAnalysis
     */
    private function primaryTypographyColorHint(array $exampleAnalysis): string
    {
        $hex = $this->firstHex($exampleAnalysis['color_palette'] ?? []);
        if ($hex !== '') {
            return "color near {$hex}";
        }

        return $this->str($exampleAnalysis['title_style'] ?? 'high contrast on background');
    }

    /**
     * @param  array<string, mixed>  $exampleAnalysis
     */
    private function secondaryTypographyColorHint(array $exampleAnalysis): string
    {
        $palette = is_array($exampleAnalysis['color_palette'] ?? null) ? $exampleAnalysis['color_palette'] : [];
        $items = [];
        foreach ($palette as $c) {
            if (is_string($c) && trim($c) !== '') {
                $items[] = trim($c);
            }
        }
        $hex = $items[1] ?? $items[0] ?? '';

        return $hex !== '' ? "color near {$hex}" : 'readable contrast vs background';
    }

    private function firstHex(mixed $colors): string
    {
        if (! is_array($colors)) {
            return '';
        }
        foreach ($colors as $c) {
            if (is_string($c) && trim($c) !== '') {
                return trim($c);
            }
        }

        return '';
    }

    private function formatHexList(mixed $colors): string
    {
        if (! is_array($colors)) {
            return '[]';
        }
        $items = [];
        foreach ($colors as $c) {
            if (is_string($c) && trim($c) !== '') {
                $items[] = trim($c);
            }
        }

        return $items === [] ? '[]' : implode(', ', $items);
    }

    private function str(mixed $v): string
    {
        if (! is_string($v)) {
            return '';
        }

        return trim($v);
    }
}
