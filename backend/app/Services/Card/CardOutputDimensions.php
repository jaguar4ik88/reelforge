<?php

namespace App\Services\Card;

/**
 * Output pixel size for PHP card from UI aspect ratio (same set as photo/card Kontext).
 */
class CardOutputDimensions
{
    /**
     * @return array{0: int, 1: int} width, height (round to 8 for common diffusion models)
     */
    public static function pixelsForAspect(string $aspect, int $longEdge = 1080): array
    {
        $ar = str_replace(' ', '', trim($aspect));
        $L = max(256, $longEdge);
        $pair = match ($ar) {
            '1:1' => [$L, $L],
            '3:4' => [(int) round($L * 3 / 4), $L],          // portrait
            '4:3' => [$L, (int) round($L * 3 / 4)],          // landscape
            '9:16' => [(int) round($L * 9 / 16), $L],         // mobile portrait
            '16:9' => [$L, (int) round($L * 9 / 16)],         // wide
            default => [$L, $L],
        };
        $w = (int) (round($pair[0] / 8) * 8);
        $h = (int) (round($pair[1] / 8) * 8);
        $w = max(256, $w);
        $h = max(256, $h);

        return [$w, $h];
    }
}
