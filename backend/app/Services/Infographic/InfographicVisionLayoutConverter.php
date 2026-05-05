<?php

namespace App\Services\Infographic;

/**
 * Maps OpenAI Vision template JSON → InfographicCanvasTemplateService manifest entry (texts only).
 * Editor viewport: 540×720; vision prompt uses ~1000px reference width for fontSize.
 */
class InfographicVisionLayoutConverter
{
    private const EDITOR_VIEW_W = 540.0;

    private const MAX_TEXTS = 24;

    /**
     * @param  array<string, mixed>  $visionResponse
     * @return array{texts: list<array<string, mixed>>}|null
     */
    public function toManifestEntry(array $visionResponse): ?array
    {
        $layers = $visionResponse['layers'] ?? null;
        if (! is_array($layers)) {
            return null;
        }

        $refW = (float) ($visionResponse['canvas']['width'] ?? 1000);
        $scale = self::EDITOR_VIEW_W / max(1.0, $refW);

        $texts = [];
        $this->walkLayers($layers, $texts, $scale);

        if ($texts === []) {
            return null;
        }

        return ['texts' => $texts];
    }

    /**
     * @param  list<array<string, mixed>>  $layers
     * @param  list<array<string, mixed>>  $texts
     */
    private function walkLayers(array $layers, array &$texts, float $scale): void
    {
        foreach ($layers as $layer) {
            if (! is_array($layer) || count($texts) >= self::MAX_TEXTS) {
                break;
            }

            $type = isset($layer['type']) ? (string) $layer['type'] : '';

            if ($type === 'badge_group' && isset($layer['children']) && is_array($layer['children'])) {
                $this->walkLayers($layer['children'], $texts, $scale);

                continue;
            }

            if ($type !== 'text') {
                continue;
            }

            if (($layer['editable'] ?? true) === false) {
                continue;
            }

            $mapped = $this->mapTextLayer($layer, $scale);
            if ($mapped !== null) {
                $texts[] = $mapped;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $layer
     * @return array<string, mixed>|null
     */
    private function mapTextLayer(array $layer, float $scale): ?array
    {
        $content = $layer['placeholder'] ?? $layer['content'] ?? $layer['label'] ?? '';
        $content = trim((string) $content);
        if ($content === '') {
            $content = (string) ($layer['label'] ?? 'Text');
        }

        $x = max(0.0, min(1.0, (float) ($layer['x'] ?? 0)));
        $y = max(0.0, min(1.0, (float) ($layer['y'] ?? 0)));
        $w = max(0.0, min(1.0, (float) ($layer['width'] ?? 0)));

        $style = $layer['style'] ?? [];
        $style = is_array($style) ? $style : [];

        $fontSizeRaw = (float) ($style['fontSize'] ?? 24);
        $fontSize = max(8.0, min(160.0, round($fontSizeRaw * $scale, 1)));

        $weightRaw = isset($style['fontWeight']) ? (string) $style['fontWeight'] : '500';
        $fontWeight = $this->normalizeFontWeight($weightRaw);

        $fill = isset($style['color']) && is_string($style['color']) ? $style['color'] : '#ffffff';
        $fill = substr($fill, 0, 32);

        $row = [
            'content' => strlen($content) > 4000 ? substr($content, 0, 4000) : $content,
            'leftRatio' => $x,
            'topRatio' => $y,
            'fontSize' => $fontSize,
            'fontWeight' => $fontWeight,
            'fill' => $fill,
            'shadow' => true,
        ];

        if ($w >= 0.03) {
            $row['kind'] = 'textbox';
            $row['widthRatio'] = $w;
        }

        return $row;
    }

    private function normalizeFontWeight(string $w): string
    {
        $w = strtolower(trim($w));
        if ($w === 'bold' || str_contains($w, 'bold')) {
            return '700';
        }
        if ($w === 'normal' || $w === 'regular') {
            return '400';
        }
        if (is_numeric($w)) {
            $n = (int) $w;

            return (string) max(100, min(900, $n));
        }

        return '500';
    }
}
