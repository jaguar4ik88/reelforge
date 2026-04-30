<?php

namespace App\Services\Card;

use RuntimeException;

/**
 * Perceived luminance 0–255 of a raster (product / background) for T2I and text-contrast.
 */
class ImageBrightness
{
    /**
     * Average luma: higher ≈ lighter image → recommend dark backdrop for the card.
     */
    public static function averageLumaFromFile(string $path): float
    {
        if (! is_readable($path)) {
            throw new RuntimeException('Image not readable for luma: '.$path);
        }

        $im = @imagecreatefromstring((string) file_get_contents($path));
        if ($im === false && extension_loaded('imagick') && class_exists(\Imagick::class)) {
            $magick = new \Imagick;
            $magick->readImage($path);
            $magick->setImageType(\Imagick::IMGTYPE_TRUECOLORALPHA);
            $magick->resizeImage(32, 32, \Imagick::FILTER_LANCZOS, 1);
            $magick->setImageFormat('png');
            $png = $magick->getImageBlob();
            $magick->clear();
            $magick->destroy();
            $im = @imagecreatefromstring($png);
        }
        if ($im === false) {
            return 128.0;
        }

        if (function_exists('imagescale')) {
            $mode = defined('IMG_BILINEAR_FIXED') ? IMG_BILINEAR_FIXED : 4;
            $s = @imagescale($im, 32, 32, $mode);
            if ($s instanceof \GdImage) {
                imagedestroy($im);
                $im = $s;
            }
        }
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            imagedestroy($im);

            return 128.0;
        }
        $sum = 0.0;
        $n = 0;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($im, $x, $y);
                if (imageistruecolor($im)) {
                    $r = ($c >> 16) & 0xFF;
                    $g = ($c >> 8) & 0xFF;
                    $b = $c & 0xFF;
                } else {
                    $p = imagecolorsforindex($im, $c);
                    $r = (int) ($p['red'] ?? 0);
                    $g = (int) ($p['green'] ?? 0);
                    $b = (int) ($p['blue'] ?? 0);
                }
                $sum += 0.299 * $r + 0.587 * $g + 0.114 * $b;
                $n++;
            }
        }
        imagedestroy($im);

        return $n > 0 ? $sum / $n : 128.0;
    }

    public static function averageLumaFromImageString(string $blob): float
    {
        $im = @imagecreatefromstring($blob);
        if ($im === false) {
            return 128.0;
        }
        if (function_exists('imagescale')) {
            $mode = defined('IMG_BILINEAR_FIXED') ? IMG_BILINEAR_FIXED : 4;
            $s = @imagescale($im, 32, 32, $mode);
            if ($s instanceof \GdImage) {
                imagedestroy($im);
                $im = $s;
            }
        }
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            imagedestroy($im);

            return 128.0;
        }
        $sum = 0.0;
        $n = 0;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($im, $x, $y);
                if (imageistruecolor($im)) {
                    $r = ($c >> 16) & 0xFF;
                    $g = ($c >> 8) & 0xFF;
                    $b = $c & 0xFF;
                } else {
                    $p = imagecolorsforindex($im, $c);
                    $r = (int) ($p['red'] ?? 0);
                    $g = (int) ($p['green'] ?? 0);
                    $b = (int) ($p['blue'] ?? 0);
                }
                $sum += 0.299 * $r + 0.587 * $g + 0.114 * $b;
                $n++;
            }
        }
        imagedestroy($im);

        return $n > 0 ? $sum / $n : 128.0;
    }

    /**
     * Average luma of a rectangular region (for per-label contrast).
     */
    public static function averageLumaFromImagickRegion(\Imagick $im, int $rx, int $ry, int $rw, int $rh): float
    {
        if (! extension_loaded('imagick')) {
            return 128.0;
        }
        $w = max(1, (int) $im->getImageWidth());
        $h = max(1, (int) $im->getImageHeight());
        $rx = max(0, min($rx, $w - 1));
        $ry = max(0, min($ry, $h - 1));
        $rw = max(1, min($rw, $w - $rx));
        $rh = max(1, min($rh, $h - $ry));
        $region = clone $im;
        try {
            $region->cropImage($rw, $rh, $rx, $ry);
            $region->resizeImage(32, 32, \Imagick::FILTER_BOX, 1);
            $region->setImageFormat('png');
            $blob = $region->getImageBlob();
        } finally {
            $region->clear();
            $region->destroy();
        }

        return self::averageLumaFromImageString($blob);
    }

    public static function averageLumaFromGdRegion(\GdImage $im, int $rx, int $ry, int $rw, int $rh): float
    {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            return 128.0;
        }
        $rx = max(0, min($rx, $w - 1));
        $ry = max(0, min($ry, $h - 1));
        $rw = max(1, min($rw, $w - $rx));
        $rh = max(1, min($rh, $h - $ry));
        if (! function_exists('imagecrop')) {
            return 128.0;
        }
        $crop = @imagecrop($im, ['x' => $rx, 'y' => $ry, 'width' => $rw, 'height' => $rh]);
        if ($crop === false) {
            return 128.0;
        }
        if (function_exists('imagescale')) {
            $mode = defined('IMG_BILINEAR_FIXED') ? IMG_BILINEAR_FIXED : 4;
            $s = @imagescale($crop, 32, 32, $mode);
            if ($s instanceof \GdImage) {
                imagedestroy($crop);
                $crop = $s;
            }
        }
        ob_start();
        if (! imagepng($crop)) {
            imagedestroy($crop);
            ob_end_clean();

            return 128.0;
        }
        $blob = (string) ob_get_clean();
        imagedestroy($crop);

        return self::averageLumaFromImageString($blob);
    }
}
