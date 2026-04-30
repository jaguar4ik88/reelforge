<?php

namespace App\Services\Card;

/**
 * Product layer position on the card canvas (centered, bottom-aligned).
 */
class CardProductPlacement
{
    /**
     * @return array{x: int, y: int, w: int, h: int}
     */
    public static function scaledRect(
        int $canvasW,
        int $canvasH,
        float $scale,
        int $bottomOffset,
        int $productOrigW,
        int $productOrigH
    ): array {
        $pw = (int) max(32, min($canvasW, $canvasW * $scale));
        $pOrigW = max(1, $productOrigW);
        $pOrigH = max(1, $productOrigH);
        $ph = (int) max(1, round($pOrigH * ($pw / $pOrigW)));
        $px = (int) (($canvasW - $pw) / 2);
        $py = (int) ($canvasH - $ph - $bottomOffset);

        return ['x' => $px, 'y' => $py, 'w' => $pw, 'h' => $ph];
    }

    /**
     * @return array{x: int, y: int, w: int, h: int}|null
     */
    public static function rectFromImagePath(
        string $path,
        int $canvasW,
        int $canvasH,
        float $scale,
        int $bottomOffset
    ): ?array {
        if (! is_readable($path)) {
            return null;
        }
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }

        return self::scaledRect(
            $canvasW,
            $canvasH,
            $scale,
            $bottomOffset,
            (int) $info[0],
            (int) $info[1]
        );
    }
}
