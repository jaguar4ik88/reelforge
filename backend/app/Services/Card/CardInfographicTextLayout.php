<?php

namespace App\Services\Card;

/**
 * Maps user wish lines to pixel positions for {@see CardComposerService}.
 * y values are the **top** of the em-box; the composer converts to baselines.
 */
class CardInfographicTextLayout
{
    /**
     * @param  list<string>  $lines
     * @param  array{x: int, y: int, w: int, h: int}|null  $productRect  Estimated product bbox on canvas (nudge labels into free space)
     * @return list<array{text: string, x: int, y: int, size: int, color: string, font: string, align: string, bold?: bool}>
     */
    public static function forLines(
        array $lines,
        int $canvasW,
        int $canvasH,
        string $accent,
        string $fontBold,
        string $fontRegular,
        ?array $productRect = null,
    ): array {
        $lines = array_values(array_filter(array_map(
            static fn (string $l) => trim($l),
            $lines
        ), static fn (string $l) => $l !== ''));
        if ($lines === []) {
            return [];
        }

        $n = min(count($lines), 7);

        $sx = $canvasW / 1080.0;
        $sy = $canvasH / 1080.0;
        $sMin = min($sx, $sy);
        $contentScale = self::contentScaleForLines($lines, $n);
        $slots = self::slotTemplates()[$n] ?? self::slotTemplates()[min(7, max(1, $n))];

        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $slot = $slots[$i];
            $useAccent = (bool) ($slot['accent'] ?? false);
            $rawSize = (int) round($slot['size'] * $sMin * $contentScale);
            $out[] = [
                'text' => $lines[$i],
                'x' => (int) round($slot['x'] * $sx),
                'y' => (int) round($slot['y'] * $sy),
                'size' => (int) max(16, min(100, $rawSize)),
                'color' => $useAccent ? $accent : '#ffffff',
                'color_role' => $useAccent ? 'accent' : 'body',
                'font' => ($slot['bold'] ?? true) ? $fontBold : $fontRegular,
                'align' => $slot['align'] ?? 'left',
            ];
        }

        if ($productRect !== null && $productRect !== []) {
            $out = self::placeTextsInFreeBandsAroundProduct($out, $productRect, $canvasW, $canvasH);
            $out = self::nudgeAwayFromProduct($out, $productRect, $canvasW, $canvasH);
        }

        return self::resolveVerticalCollisions($out, $canvasW, $canvasH);
    }

    /**
     * Shrink type when lines are long or numerous (readability on card).
     *
     * @param  list<string>  $lines
     */
    private static function contentScaleForLines(array $lines, int $n): float
    {
        $maxLen = 0;
        foreach ($lines as $l) {
            $t = trim($l);
            if ($t === '') {
                continue;
            }
            $maxLen = max($maxLen, function_exists('mb_strlen') ? mb_strlen($t) : strlen($t));
        }
        $a = 1.0;
        if ($maxLen > 42) {
            $a *= 0.76;
        } elseif ($maxLen > 32) {
            $a *= 0.85;
        } elseif ($maxLen > 24) {
            $a *= 0.91;
        } elseif ($maxLen > 18) {
            $a *= 0.96;
        }
        if ($n >= 6) {
            $a *= 0.87;
        } elseif ($n >= 5) {
            $a *= 0.91;
        } elseif ($n >= 4) {
            $a *= 0.95;
        }

        return max(0.64, min(1.0, $a));
    }

    /**
     * Place labels in top/bottom (or side) gutters using pixel product bbox from vision.
     *
     * @param  list<array<string, mixed>>  $texts
     * @param  array{x: int, y: int, w: int, h: int}  $productRect
     * @return list<array<string, mixed>>
     */
    private static function placeTextsInFreeBandsAroundProduct(
        array $texts,
        array $productRect,
        int $canvasW,
        int $canvasH,
    ): array {
        $px = (int) $productRect['x'];
        $py = (int) $productRect['y'];
        $pw = max(1, (int) $productRect['w']);
        $ph = max(1, (int) $productRect['h']);

        $padT = (int) max(28, round($canvasH * 0.028));
        $padX = (int) max(36, round($canvasW * 0.036));

        $productTop = $py;
        $productBottom = $py + $ph;

        $topSpace = max(0, $productTop - $padT);
        $bottomSpace = max(0, $canvasH - $productBottom - $padT);

        $preferBottomForRight = $bottomSpace > $topSpace + (int) ($canvasH * 0.05)
            && $bottomSpace >= (int) ($canvasH * 0.09);

        $leftIdx = [];
        $rightIdx = [];
        foreach ($texts as $idx => $t) {
            if (! is_array($t)) {
                continue;
            }
            if (strtolower((string) ($t['align'] ?? 'left')) === 'right') {
                $rightIdx[] = $idx;
            } else {
                $leftIdx[] = $idx;
            }
        }

        $xLeft = $padX;
        $xRight = $canvasW - $padX;

        $leftSplit = 0;
        $probeY = $padT;
        foreach ($leftIdx as $idx) {
            $sz = (int) ($texts[$idx]['size'] ?? 32);
            $step = (int) max(40, round($sz * 1.22));
            $gap = (int) max(6, round($sz * 0.07));
            if ($probeY + $step <= $productTop - 10) {
                $leftSplit++;
                $probeY += $step + $gap;
            } else {
                break;
            }
        }

        $y = $padT;
        for ($k = 0; $k < $leftSplit; $k++) {
            $idx = $leftIdx[$k];
            $sz = (int) ($texts[$idx]['size'] ?? 32);
            $step = (int) max(40, round($sz * 1.22));
            $gap = (int) max(6, round($sz * 0.07));
            $texts[$idx]['x'] = $xLeft;
            $texts[$idx]['y'] = $y;
            $y += $step + $gap;
        }

        if ($leftSplit < count($leftIdx)) {
            $yB = $productBottom + (int) max(20, $canvasH * 0.02);
            for ($k = $leftSplit; $k < count($leftIdx); $k++) {
                $idx = $leftIdx[$k];
                $sz = (int) ($texts[$idx]['size'] ?? 32);
                $step = (int) max(40, round($sz * 1.22));
                $gap = (int) max(6, round($sz * 0.07));
                $texts[$idx]['x'] = $xLeft;
                $texts[$idx]['y'] = min($yB, $canvasH - $padT - $sz);
                $yB = (int) $texts[$idx]['y'] + $step + $gap;
            }
        }

        $nR = count($rightIdx);
        if ($nR > 0) {
            if ($preferBottomForRight) {
                $yR = $productBottom + (int) max(22, $canvasH * 0.022);
                foreach ($rightIdx as $idx) {
                    $sz = (int) ($texts[$idx]['size'] ?? 28);
                    $step = (int) max(38, round($sz * 1.14));
                    $gap = (int) max(5, round($sz * 0.06));
                    $texts[$idx]['x'] = $xRight;
                    $texts[$idx]['y'] = min($yR, $canvasH - $padT - $sz);
                    $texts[$idx]['align'] = 'right';
                    $yR = (int) $texts[$idx]['y'] + $step + $gap;
                }
            } else {
                $yR = min($productTop - 16, $canvasH - $padT - 48);
                foreach (array_reverse($rightIdx) as $idx) {
                    $sz = (int) ($texts[$idx]['size'] ?? 28);
                    $step = (int) max(38, round($sz * 1.14));
                    $gap = (int) max(5, round($sz * 0.06));
                    $yR -= $step;
                    $texts[$idx]['x'] = $xRight;
                    $texts[$idx]['y'] = max($padT, $yR);
                    $texts[$idx]['align'] = 'right';
                    $yR = (int) $texts[$idx]['y'] - $gap;
                }
            }
        }

        return $texts;
    }

    /**
     * Prevent stacked lines in the same column from overlapping vertically (same x-alignment bucket).
     *
     * @param  list<array<string, mixed>>  $texts
     * @return list<array<string, mixed>>
     */
    public static function resolveVerticalCollisions(array $texts, int $canvasW, int $canvasH): array
    {
        if (count($texts) < 2) {
            return $texts;
        }

        $mid = (int) ($canvasW / 2);
        $groups = [[], []];
        foreach ($texts as $idx => $t) {
            if (! is_array($t) || ! isset($t['y'], $t['size'])) {
                continue;
            }
            $align = strtolower((string) ($t['align'] ?? 'left'));
            $x = (int) ($t['x'] ?? 0);
            $rightColumn = $align === 'right' || ($align !== 'left' && $x >= $mid);
            $groups[$rightColumn ? 1 : 0][] = $idx;
        }

        foreach ($groups as $indices) {
            if (count($indices) < 2) {
                continue;
            }
            usort($indices, static fn (int $a, int $b): int => ((int) $texts[$a]['y']) <=> ((int) $texts[$b]['y']));
            for ($j = 1; $j < count($indices); $j++) {
                $pi = $indices[$j - 1];
                $ci = $indices[$j];
                $py = (int) $texts[$pi]['y'];
                $cy = (int) $texts[$ci]['y'];
                $ps = (int) ($texts[$pi]['size'] ?? 32);
                $cs = (int) ($texts[$ci]['size'] ?? 32);
                $minGap = (int) max(16, (int) round(max($ps, $cs) * 0.42));
                if ($cy - $py < $minGap) {
                    $texts[$ci]['y'] = min($canvasH - (int) round($cs * 1.4), $py + $minGap);
                }
            }
        }

        return $texts;
    }

    /**
     * @param  list<array<string, mixed>>  $texts
     * @param  array{x: int, y: int, w: int, h: int}  $productRect
     * @return list<array<string, mixed>>
     */
    public static function nudgeAwayFromProduct(array $texts, array $productRect, int $canvasW, int $canvasH): array
    {
        $m = 28;
        $box = [
            'x' => (int) $productRect['x'] - $m,
            'y' => (int) $productRect['y'] - $m,
            'w' => (int) $productRect['w'] + 2 * $m,
            'h' => (int) $productRect['h'] + 2 * $m,
        ];
        $out = [];
        foreach ($texts as $t) {
            if (! is_array($t) || ! isset($t['text'], $t['x'], $t['y'], $t['size'])) {
                $out[] = $t;

                continue;
            }
            $text = (string) $t['text'];
            $size = (int) $t['size'];
            $align = strtolower((string) ($t['align'] ?? 'left'));
            $x = (int) $t['x'];
            $yTop = (int) $t['y'];
            $tw = (int) max(48, min((int) ($canvasW * 0.92), self::estimateTextWidthChars($text, $size)));
            $th = (int) max(20, (int) round($size * 1.35));
            $left = $align === 'right' ? $x - $tw : ($align === 'center' ? (int) ($x - $tw / 2) : $x);
            $left = max(8, min($left, $canvasW - $tw - 8));
            $guard = 0;
            while (self::rectsOverlap($left, $yTop, $tw, $th, $box['x'], $box['y'], $box['w'], $box['h'])
                && $yTop > 36
                && $guard < 80) {
                $yTop -= 14;
                $guard++;
            }
            $t['y'] = $yTop;
            $out[] = $t;
        }

        return $out;
    }

    private static function estimateTextWidthChars(string $text, int $fontSize): float
    {
        $len = function_exists('mb_strlen') ? (int) mb_strlen($text) : strlen($text);

        return max(8, $len) * $fontSize * 0.58;
    }

    private static function rectsOverlap(
        int $ax,
        int $ay,
        int $aw,
        int $ah,
        int $bx,
        int $by,
        int $bw,
        int $bh
    ): bool {
        return $ax < $bx + $bw && $ax + $aw > $bx && $ay < $by + $bh && $ay + $ah > $by;
    }

    /**
     * @return array<int, list<array{x: int, y: int, size: int, align: string, bold: bool, accent: bool}>>
     */
    private static function slotTemplates(): array
    {
        return [
            1 => [
                ['x' => 60, 'y' => 90, 'size' => 88, 'align' => 'left', 'bold' => true, 'accent' => true],
            ],
            2 => [
                ['x' => 60, 'y' => 80, 'size' => 72, 'align' => 'left', 'bold' => true, 'accent' => true],
                ['x' => 60, 'y' => 170, 'size' => 56, 'align' => 'left', 'bold' => true, 'accent' => false],
            ],
            3 => [
                ['x' => 60, 'y' => 80, 'size' => 72, 'align' => 'left', 'bold' => true, 'accent' => true],
                ['x' => 60, 'y' => 160, 'size' => 52, 'align' => 'left', 'bold' => true, 'accent' => false],
                ['x' => 60, 'y' => 480, 'size' => 48, 'align' => 'left', 'bold' => true, 'accent' => true],
            ],
            4 => [
                ['x' => 60, 'y' => 80, 'size' => 64, 'align' => 'left', 'bold' => true, 'accent' => true],
                ['x' => 60, 'y' => 150, 'size' => 48, 'align' => 'left', 'bold' => true, 'accent' => false],
                ['x' => 1000, 'y' => 420, 'size' => 34, 'align' => 'right', 'bold' => true, 'accent' => false],
                ['x' => 1000, 'y' => 500, 'size' => 34, 'align' => 'right', 'bold' => true, 'accent' => true],
            ],
            5 => [
                ['x' => 60, 'y' => 90, 'size' => 64, 'align' => 'left', 'bold' => true, 'accent' => true],
                ['x' => 60, 'y' => 165, 'size' => 48, 'align' => 'left', 'bold' => true, 'accent' => false],
                ['x' => 60, 'y' => 500, 'size' => 40, 'align' => 'left', 'bold' => true, 'accent' => true],
                ['x' => 1000, 'y' => 400, 'size' => 32, 'align' => 'right', 'bold' => true, 'accent' => false],
                ['x' => 1000, 'y' => 450, 'size' => 32, 'align' => 'right', 'bold' => true, 'accent' => false],
            ],
            6 => [
                ['x' => 50, 'y' => 70, 'size' => 56, 'align' => 'left', 'bold' => true, 'accent' => true],
                ['x' => 50, 'y' => 130, 'size' => 40, 'align' => 'left', 'bold' => true, 'accent' => false],
                ['x' => 50, 'y' => 190, 'size' => 32, 'align' => 'left', 'bold' => true, 'accent' => true],
                ['x' => 1000, 'y' => 360, 'size' => 30, 'align' => 'right', 'bold' => true, 'accent' => false],
                ['x' => 1000, 'y' => 410, 'size' => 30, 'align' => 'right', 'bold' => true, 'accent' => false],
                ['x' => 1000, 'y' => 460, 'size' => 30, 'align' => 'right', 'bold' => true, 'accent' => true],
            ],
            7 => [
                ['x' => 50, 'y' => 60, 'size' => 52, 'align' => 'left', 'bold' => true, 'accent' => true],
                ['x' => 50, 'y' => 115, 'size' => 36, 'align' => 'left', 'bold' => true, 'accent' => false],
                ['x' => 50, 'y' => 160, 'size' => 30, 'align' => 'left', 'bold' => true, 'accent' => true],
                ['x' => 1000, 'y' => 330, 'size' => 28, 'align' => 'right', 'bold' => true, 'accent' => false],
                ['x' => 1000, 'y' => 370, 'size' => 28, 'align' => 'right', 'bold' => true, 'accent' => false],
                ['x' => 1000, 'y' => 410, 'size' => 28, 'align' => 'right', 'bold' => true, 'accent' => true],
                ['x' => 1000, 'y' => 450, 'size' => 28, 'align' => 'right', 'bold' => true, 'accent' => false],
            ],
        ];
    }
}
