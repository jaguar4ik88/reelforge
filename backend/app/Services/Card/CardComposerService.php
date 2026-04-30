<?php

namespace App\Services\Card;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class CardComposerService
{
    public function __construct(
        private readonly int $width = 1080,
        private readonly int $height = 1080
    ) {
        if ($this->width < 1 || $this->height < 1) {
            throw new RuntimeException('Invalid card dimensions.');
        }
    }

    public static function fromConfig(): self
    {
        $w = (int) config('platform.card.php_composite.width', 1080);
        $h = (int) config('platform.card.php_composite.height', 1080);

        return new self($w, $h);
    }

    /**
     * Resolve a .ttf/.otf path: storage/app/fonts first, then OS defaults (incl. macOS Arial Unicode for Cyrillic).
     *
     * @throws RuntimeException
     */
    public static function resolveTtfPath(string $fontName): string
    {
        if ($fontName !== '' && is_file($fontName) && is_readable($fontName)) {
            $low = strtolower($fontName);
            if (str_ends_with($low, '.ttf') || str_ends_with($low, '.otf')) {
                return $fontName;
            }
        }

        $tried = [];
        $base = basename($fontName);
        $tryOne = function (string $p) use (&$tried): ?string {
            $tried[] = $p;
            if (is_file($p) && is_readable($p)) {
                $low = strtolower($p);
                if (str_ends_with($low, '.ttf') || str_ends_with($low, '.otf')) {
                    return $p;
                }
            }

            return null;
        };

        if (($r = $tryOne(storage_path('app/fonts/'.$base))) !== null) {
            return $r;
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            foreach ([
                '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
                '/System/Library/Fonts/Supplemental/Arial.ttf',
                '/Library/Fonts/Arial Unicode.ttf',
                '/System/Library/Fonts/Supplemental/Times New Roman.ttf',
            ] as $p) {
                if (($r = $tryOne($p)) !== null) {
                    return $r;
                }
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            foreach ([
                'C:\\Windows\\Fonts\\arial.ttf',
                'C:\\Windows\\Fonts\\arialbd.ttf',
                'C:\\Windows\\Fonts\\arialuni.ttf',
            ] as $p) {
                if (($r = $tryOne($p)) !== null) {
                    return $r;
                }
            }
        }

        foreach (['/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf'] as $p) {
            if (($r = $tryOne($p)) !== null) {
                return $r;
            }
        }

        throw new RuntimeException(
            'No TTF/OTF font for card text. Tried: '.implode(', ', $tried)
            .'. Add Montserrat (or any .ttf) to storage/app/fonts/ and set font_bold / font_regular in config/platform.php card.php_composite, or install fonts on the server.'
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @param  array<string, mixed>  $options
     * @param  string|null  $productPngPath  Cut-out with transparency; null/empty to draw only T2I background and text
     * @return string Absolute local filesystem path to the written JPEG
     */
    public function compose(
        string $bgImageSource,
        ?string $productPngPath,
        array $texts,
        array $options = [],
        ?string $outputPath = null
    ): string {
        foreach ($texts as $t) {
            if (is_array($t) && isset($t['font']) && (string) $t['font'] !== '') {
                self::resolveTtfPath((string) $t['font']);
            }
        }

        if (extension_loaded('imagick')) {
            return $this->composeWithImagick($bgImageSource, $productPngPath, $texts, $options, $outputPath);
        }
        if (extension_loaded('gd')) {
            return $this->composeWithGd($bgImageSource, $productPngPath, $texts, $options, $outputPath);
        }

        throw new RuntimeException('Card composition requires the PHP "imagick" or "gd" extension.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @param  array<string, mixed>  $options
     */
    private function composeWithImagick(
        string $bgImageSource,
        ?string $productPngPath,
        array $texts,
        array $options,
        ?string $outputPath
    ): string {
        $w = (int) ($options['width'] ?? $this->width);
        $h = (int) ($options['height'] ?? $this->height);

        $bgBlob = $this->loadImageBlob($bgImageSource);

        $canvas = new \Imagick;
        $canvas->newImage($w, $h, new \ImagickPixel('white'));
        $canvas->setImageFormat('jpeg');
        $canvas->setImageCompression(\Imagick::COMPRESSION_JPEG);
        $canvas->setImageCompressionQuality(92);

        $bg = new \Imagick;
        $bg->readImageBlob($bgBlob);
        $bg->resizeImage($w, $h, \Imagick::FILTER_LANCZOS, 1);
        $bg->setImageFormat('jpeg');
        $texts = $this->applyTextContrastForImagickBackground($texts, $bg, $options);
        $canvas->compositeImage($bg, \Imagick::COMPOSITE_OVER, 0, 0);
        $bg->destroy();

        if ($options['diagonal_lines'] ?? false) {
            $this->drawDiagonalAccentsImagick($canvas, (string) ($options['accent_color'] ?? '#ffffff'), $w, $h);
        }

        $productRect = null;

        if ($this->hasProductLayer($productPngPath)) {
            $product = new \Imagick;
            if (! is_file((string) $productPngPath) || ! is_readable((string) $productPngPath)) {
                $product->clear();
                $product->destroy();
                $canvas->destroy();
                throw new RuntimeException('Product image is not readable: '.(string) $productPngPath);
            }
            $product->readImage($productPngPath);
            $product->setImageFormat('png');

            $scale = (float) ($options['product_scale'] ?? 0.6);
            $pw = (int) max(32, min($w, $w * $scale));
            $pOrigW = (int) max(1, $product->getImageWidth());
            $pOrigH = (int) max(1, $product->getImageHeight());
            $ph = (int) round($pOrigH * ($pw / $pOrigW));
            $product->resizeImage($pw, $ph, \Imagick::FILTER_LANCZOS, 1);
            if ($options['product_shadow'] ?? true) {
                $this->drawProductShadowImagick(
                    $canvas,
                    $w,
                    $h,
                    $pw,
                    $ph,
                    (int) ($options['product_bottom_offset'] ?? 80)
                );
            }
            $px = (int) (($w - $pw) / 2);
            $py = (int) ($h - $ph - (int) ($options['product_bottom_offset'] ?? 80));
            $canvas->compositeImage($product, \Imagick::COMPOSITE_OVER, $px, $py);
            $product->destroy();
            $productRect = ['x' => $px, 'y' => $py, 'w' => $pw, 'h' => $ph];
        } else {
            $productRect = $this->productRectFromOptionsWhenNoLayer($options, $w, $h);
        }

        if ($this->optionBool($options, 'infographic_callouts', true) && $productRect !== null && count($texts) >= 2) {
            $this->drawInfographicCalloutsImagick($canvas, $texts, $productRect, $w, $h);
        }

        $texts = $this->refineTextsForLocalContrastImagick($texts, $canvas, $productRect, $options);

        foreach ($texts as $t) {
            if (! is_array($t) || ! isset($t['text'], $t['x'], $t['y'], $t['size'])) {
                continue;
            }
            $this->drawTextImagick(
                $canvas,
                (string) $t['text'],
                (int) $t['x'],
                (int) $t['y'],
                (int) $t['size'],
                (string) ($t['color'] ?? '#ffffff'),
                (string) ($t['align'] ?? 'left'),
                $this->fontPathForLabel((string) ($t['font'] ?? 'Montserrat-Bold.ttf')),
                (float) ($t['stroke_width'] ?? 0.0),
                isset($t['stroke_color']) ? (string) $t['stroke_color'] : null,
            );
        }

        $out = $outputPath ?? $this->makeTempJpegPath();
        $this->ensureDir(dirname($out));
        $canvas->writeImage($out);
        $canvas->destroy();

        return $out;
    }

    private function makeTempJpegPath(): string
    {
        $dir = storage_path('app/public/cards');
        $this->ensureDir($dir);

        return $dir.'/'.uniqid('card_', true).'.jpg';
    }

    private function ensureDir(string $path): void
    {
        if (is_dir($path)) {
            return;
        }
        File::makeDirectory($path, 0755, true);
    }

    private function hasProductLayer(?string $productPngPath): bool
    {
        if ($productPngPath === null || $productPngPath === '') {
            return false;
        }

        return is_file($productPngPath) && is_readable($productPngPath);
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @param  array<string, mixed>  $options
     */
    private function composeWithGd(
        string $bgImageSource,
        ?string $productPngPath,
        array $texts,
        array $options,
        ?string $outputPath
    ): string {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('GD is loaded but imagecreatetruecolor is unavailable.');
        }

        $w = (int) ($options['width'] ?? $this->width);
        $h = (int) ($options['height'] ?? $this->height);
        $bgBlob = $this->loadImageBlob($bgImageSource);
        $bgRes = @imagecreatefromstring($bgBlob);
        if ($bgRes === false) {
            throw new RuntimeException('Could not decode background image (GD).');
        }
        $bgScaled = $this->gdScale($bgRes, $w, $h);
        imagedestroy($bgRes);
        $bgRes = $bgScaled;
        $texts = $this->applyTextContrastForGdBackground($texts, $bgRes, $options);

        $canvas = imagecreatetruecolor($w, $h);
        if ($canvas === false) {
            imagedestroy($bgRes);
            throw new RuntimeException('Could not create GD canvas.');
        }
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $bgRes, 0, 0, 0, 0, $w, $h, imagesx($bgRes), imagesy($bgRes));
        imagedestroy($bgRes);

        if ($options['diagonal_lines'] ?? false) {
            $this->drawDiagonalAccentsGd($canvas, (string) ($options['accent_color'] ?? '#ffffff'), $w, $h);
        }

        $productRect = null;

        if ($this->hasProductLayer($productPngPath)) {
            if (! is_file((string) $productPngPath) || ! is_readable((string) $productPngPath)) {
                imagedestroy($canvas);
                throw new RuntimeException('Product image is not readable: '.(string) $productPngPath);
            }

            $pbytes = @file_get_contents((string) $productPngPath);
            if ($pbytes === false) {
                imagedestroy($canvas);
                throw new RuntimeException('Could not read product image file.');
            }
            $product = @imagecreatefromstring($pbytes);
            if ($product === false) {
                $product = @imagecreatefromstring((string) file_get_contents((string) $productPngPath));
            }
            if ($product === false) {
                imagedestroy($canvas);
                throw new RuntimeException('Could not decode product image (GD).');
            }

            $pOrigW = max(1, imagesx($product));
            $pOrigH = max(1, imagesy($product));
            $scale = (float) ($options['product_scale'] ?? 0.6);
            $pw = (int) max(32, min($w, $w * $scale));
            $ph = (int) max(1, round($pOrigH * ($pw / $pOrigW)));
            $productScaled = $this->gdScale($product, $pw, $ph);
            imagedestroy($product);
            $product = $productScaled;

            if ($options['product_shadow'] ?? true) {
                $this->drawProductShadowGd($canvas, $w, $h, $pw, $ph, (int) ($options['product_bottom_offset'] ?? 80));
            }

            $px = (int) (($w - $pw) / 2);
            $py = (int) ($h - $ph - (int) ($options['product_bottom_offset'] ?? 80));
            imagecopy($canvas, $product, $px, $py, 0, 0, $pw, $ph);
            imagedestroy($product);
            $productRect = ['x' => $px, 'y' => $py, 'w' => $pw, 'h' => $ph];
        } else {
            $productRect = $this->productRectFromOptionsWhenNoLayer($options, $w, $h);
        }

        if ($this->optionBool($options, 'infographic_callouts', true) && $productRect !== null && count($texts) >= 2) {
            $this->drawInfographicCalloutsGd($canvas, $texts, $productRect, $w, $h);
        }

        $texts = $this->refineTextsForLocalContrastGd($texts, $canvas, $productRect, $options);

        foreach ($texts as $t) {
            if (! is_array($t) || ! isset($t['text'], $t['x'], $t['y'], $t['size'])) {
                continue;
            }
            $this->drawTextGd(
                $canvas,
                (string) $t['text'],
                (int) $t['x'],
                (int) $t['y'],
                (int) $t['size'],
                (string) ($t['color'] ?? '#ffffff'),
                (string) ($t['align'] ?? 'left'),
                $w,
                $this->fontPathForLabel((string) ($t['font'] ?? 'Montserrat-Bold.ttf')),
                (float) ($t['stroke_width'] ?? 0.0),
                isset($t['stroke_color']) ? (string) $t['stroke_color'] : null,
            );
        }

        $out = $outputPath ?? $this->makeTempJpegPath();
        $this->ensureDir(dirname($out));
        if (! imagejpeg($canvas, $out, 92)) {
            imagedestroy($canvas);
            throw new RuntimeException('Failed to write output JPEG (GD).');
        }
        imagedestroy($canvas);

        return $out;
    }

    private function loadImageBlob(string $bgImageSource): string
    {
        if (preg_match('#^https?://#i', $bgImageSource) === 1) {
            try {
                $response = Http::timeout(120)
                    ->withOptions(['http_errors' => true])
                    ->get($bgImageSource);
                if (! $response->ok()) {
                    throw new RequestException($response);
                }
                if ($response->body() === '') {
                    throw new RuntimeException('Empty body from background URL.');
                }

                return $response->body();
            } catch (RequestException|Throwable $e) {
                throw new RuntimeException('Failed to load background from URL: '.$e->getMessage());
            }
        }
        if (is_file($bgImageSource) && is_readable($bgImageSource)) {
            $b = @file_get_contents($bgImageSource);
            if ($b === false || $b === '') {
                throw new RuntimeException('Failed to read background file.');
            }

            return $b;
        }
        throw new RuntimeException('Invalid background source.');
    }

    private function fontPathForLabel(string $fontName): string
    {
        return self::resolveTtfPath($fontName);
    }

    private function drawTextImagick(
        \Imagick $canvas,
        string $text,
        int $x,
        int $yTop,
        int $size,
        string $color,
        string $align,
        string $fontPath,
        float $strokeWidth = 0.0,
        ?string $strokeColor = null,
    ): void {
        if ($text === '' || ! is_file($fontPath)) {
            return;
        }

        $draw = new \ImagickDraw;
        $draw->setFont($fontPath);
        $draw->setFontSize($size);
        $draw->setFillColor(new \ImagickPixel($this->normalizeColor($color)));
        $draw->setTextAntialias(true);
        if ($strokeWidth > 0.05 && $strokeColor !== null && $strokeColor !== '') {
            $draw->setStrokeColor(new \ImagickPixel($this->normalizeColor($strokeColor)));
            $draw->setStrokeWidth($strokeWidth);
            $draw->setStrokeAntialias(true);
        } else {
            $draw->setStrokeWidth(0);
        }
        $align = strtolower($align);
        // ImageMagick: 1=Left, 2=Center, 3=Right
        $map = match ($align) {
            'center' => 2,
            'right' => 3,
            default => 1,
        };
        $draw->setTextAlignment($map);

        $m = $canvas->queryFontMetrics($draw, $text);
        $asc = (float) ($m['ascender'] ?? 0);
        $baselineY = (int) ($yTop + (int) $asc);
        if ($asc === 0.0) {
            $baselineY = (int) ($yTop + (int) round($size * 0.8));
        }

        $canvas->annotateImage($draw, (float) $x, (float) $baselineY, 0, $text);
    }

    private function drawTextGd(
        \GdImage $canvas,
        string $text,
        int $x,
        int $yTop,
        int $size,
        string $color,
        string $align,
        int $_canvasW,
        string $fontPath,
        float $strokeWidth = 0.0,
        ?string $strokeColor = null,
    ): void {
        if ($text === '' || $size < 1 || ! function_exists('imagettftext') || ! is_file($fontPath)) {
            return;
        }
        $textCol = $this->colorToRgb($color, $canvas, false);
        if (! is_int($textCol) || $textCol < 0) {
            $textCol = imagecolorallocate($canvas, 255, 255, 255);
        }
        $align = strtolower($align);
        if (function_exists('mb_convert_encoding')) {
            $text = (string) mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        $box = @imagettfbbox($size, 0, $fontPath, $text);
        $tw = 0;
        if (is_array($box)) {
            $tw = (int) abs($box[2] - $box[0]);
        }
        $xDraw = $x;
        if ($align === 'right') {
            $xDraw = $x - $tw;
        } elseif ($align === 'center') {
            $xDraw = (int) ($x - $tw / 2);
        }
        $hLine = 0;
        if (is_array($box)) {
            $hLine = (int) abs($box[7] - $box[1]);
        }
        if ($hLine < 1) {
            $hLine = $size;
        }
        $baselineY = (int) ($yTop + $hLine);
        if ($strokeWidth > 0.4 && $strokeColor !== null && $strokeColor !== '') {
            $strokeCol = $this->colorToRgb($strokeColor, $canvas, false);
            if (is_int($strokeCol) && $strokeCol >= 0) {
                $o = max(1, min(3, (int) round($strokeWidth)));
                foreach ([[-$o, 0], [$o, 0], [0, -$o], [0, $o], [-$o, -$o], [$o, -$o], [-$o, $o], [$o, $o]] as [$ox, $oy]) {
                    @imagettftext($canvas, $size, 0, $xDraw + $ox, $baselineY + $oy, $strokeCol, $fontPath, $text);
                }
            }
        }
        $ok = @imagettftext($canvas, $size, 0, $xDraw, $baselineY, $textCol, $fontPath, $text);
        if ($ok === false) {
            // ignore — avoid breaking pipeline on TTF load race
        }
    }

    private function colorToRgb(string $hex, \GdImage $image, bool $withAlpha = false)
    {
        $hex = $this->normalizeColor($hex);
        if (! str_starts_with($hex, '#') || (strlen($hex) !== 7 && strlen($hex) !== 9)) {
            $hex = '#ffffff';
        }
        $h = ltrim($hex, '#');
        if (strlen($h) === 6) {
            $r = hexdec(substr($h, 0, 2));
            $g = hexdec(substr($h, 2, 2));
            $b = hexdec(substr($h, 4, 2));
            if ($withAlpha && function_exists('imagecolorallocatealpha')) {
                // subtle stroke for diagonals
                return (int) imagecolorallocatealpha($image, (int) $r, (int) $g, (int) $b, 90);
            }

            return (int) imagecolorallocate($image, (int) $r, (int) $g, (int) $b);
        }

        $r = hexdec(substr($h, 0, 2));
        $g = hexdec(substr($h, 2, 2));
        $b = hexdec(substr($h, 4, 2));
        if (function_exists('imagecolorallocatealpha') && $withAlpha) {
            $a = 127 - (int) (hexdec(substr($h, 6, 2)) / 2);

            return (int) imagecolorallocatealpha($image, (int) $r, (int) $g, (int) $b, max(0, min(127, $a)));
        }

        return (int) imagecolorallocate($image, (int) $r, (int) $g, (int) $b);
    }

    private function normalizeColor(string $c): string
    {
        $c = trim($c);
        if ($c === '') {
            return '#ffffff';
        }
        if (preg_match('/^#?[0-9a-fA-F]{6}$/', ltrim($c, '#')) === 1) {
            $h = ltrim($c, '#');
            if ($h[0] !== '#') {
                $h = '#'.$h;
            } else {
                $h = $c;
            }

            return (string) (str_starts_with($h, '#') ? $h : '#'.$h);
        }

        return $c[0] === '#' ? $c : '#'.$c;
    }

    private function drawDiagonalAccentsImagick(\Imagick $canvas, string $color, int $w, int $h): void
    {
        $draw = new \ImagickDraw;
        $draw->setStrokeColor(new \ImagickPixel($this->normalizeColor($color)));
        $draw->setStrokeWidth(2);
        $draw->setStrokeOpacity(0.25);
        $draw->setFillColor(new \ImagickPixel('none'));
        $sw = 1080.0;
        $sh = 1080.0;
        $sx = $w / $sw;
        $sy = $h / $sh;
        $line = function (int $x1, int $y1, int $x2, int $y2) use ($draw, $sx, $sy): void {
            $draw->line((int) ($x1 * $sx), (int) ($y1 * $sy), (int) ($x2 * $sx), (int) ($y2 * $sy));
        };
        $line(0, 400, 400, 0);
        $line(0, 500, 500, 0);
        $line(600, 1080, 1080, 600);
        $line(700, 1080, 1080, 700);
        $canvas->drawImage($draw);
    }

    private function drawProductShadowImagick(\Imagick $canvas, int $w, int $h, int $pw, int $ph, int $bottomOff): void
    {
        $py = (int) ($h - $ph - $bottomOff);
        $draw = new \ImagickDraw;
        $draw->setFillColor(new \ImagickPixel('rgba(0,0,0,0.22)'));
        $draw->ellipse((float) ($w / 2), (float) ($py + $ph - 2), (float) ($pw * 0.45), 18, 0, 360);
        $canvas->drawImage($draw);
    }

    private function drawProductShadowGd(
        \GdImage $canvas,
        int $w,
        int $h,
        int $pw,
        int $ph,
        int $bottomOff
    ): void {
        $py = (int) ($h - $ph - $bottomOff);
        if (! function_exists('imagefilledellipse')) {
            return;
        }
        $c = imagecolorallocatealpha($canvas, 0, 0, 0, 100);
        imagefilledellipse(
            $canvas,
            (int) ($w / 2),
            (int) ($py + $ph - 2),
            (int) max(4, (int) ($pw * 0.9)),
            20,
            $c
        );
    }

    private function drawDiagonalAccentsGd(\GdImage $canvas, string $color, int $w, int $h): void
    {
        if (! function_exists('imageline')) {
            return;
        }
        $c = $this->colorToRgb($this->normalizeColor($color), $canvas, true);
        if (! is_int($c) || $c < 0) {
            $c = (int) imagecolorallocate($canvas, 200, 200, 200);
        }
        $sw = 1080.0;
        $sh = 1080.0;
        $sx = $w / $sw;
        $sy = $h / $sh;
        $l = function (int $x1, int $y1, int $x2, int $y2) use ($canvas, $c, $sx, $sy): void {
            imageline(
                $canvas,
                (int) ($x1 * $sx),
                (int) ($y1 * $sy),
                (int) ($x2 * $sx),
                (int) ($y2 * $sy),
                $c
            );
        };
        $l(0, 400, 400, 0);
        $l(0, 500, 500, 0);
        $l(600, 1080, 1080, 600);
        $l(700, 1080, 1080, 700);
    }

    private function gdScale(\GdImage $src, int $newW, int $newH): \GdImage
    {
        if (! function_exists('imagecreatetruecolor') || $newW < 1 || $newH < 1) {
            imagedestroy($src);
            throw new RuntimeException('Invalid GD scale dimensions.');
        }
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            imagedestroy($src);
            throw new RuntimeException('Invalid GD source dimensions.');
        }
        if (function_exists('imagescale')) {
            $mode = defined('IMG_BILINEAR_FIXED') ? IMG_BILINEAR_FIXED : 4;
            $s = @imagescale($src, $newW, $newH, $mode);
            if ($s instanceof \GdImage) {
                imagedestroy($src);

                return $s;
            }
        }
        $dst = imagecreatetruecolor($newW, $newH);
        if ($dst === false) {
            imagedestroy($src);
            throw new RuntimeException('imagecreatetruecolor failed during resize.');
        }
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);

        return $dst;
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private function applyTextContrastForImagickBackground(array $texts, \Imagick $bg, array $options): array
    {
        if (! $this->optionBool($options, 'auto_text_contrast', true) || $texts === []) {
            return $texts;
        }
        if (! $bg->getImageWidth()) {
            return $texts;
        }
        $snap = clone $bg;
        $snap->setImageFormat('png');
        $luma = ImageBrightness::averageLumaFromImageString($snap->getImageBlob());
        $snap->clear();
        $snap->destroy();

        return $this->remapTextColorsForLuma(
            $texts,
            $luma,
            (string) ($options['user_accent'] ?? $options['accent_color'] ?? '#d4af37')
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private function applyTextContrastForGdBackground(array $texts, \GdImage $bg, array $options): array
    {
        if (! $this->optionBool($options, 'auto_text_contrast', true) || $texts === []) {
            return $texts;
        }
        if (! function_exists('imagepng')) {
            return $texts;
        }
        ob_start();
        imagepng($bg);
        $blob = (string) ob_get_clean();
        $luma = ImageBrightness::averageLumaFromImageString($blob);

        return $this->remapTextColorsForLuma(
            $texts,
            $luma,
            (string) ($options['user_accent'] ?? $options['accent_color'] ?? '#d4af37')
        );
    }

    private function optionBool(array $options, string $k, bool $def): bool
    {
        if (! array_key_exists($k, $options)) {
            return $def;
        }

        return (bool) filter_var($options[$k], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @return array<int, array<string, mixed>>
     */
    private function remapTextColorsForLuma(array $texts, float $bgLuma, string $userAccent): array
    {
        $p = $this->textPalette($bgLuma, $this->normalizeColor($userAccent));
        $out = [];
        foreach ($texts as $t) {
            if (! is_array($t) || ! isset($t['text'])) {
                $out[] = $t;

                continue;
            }
            $role = (string) ($t['color_role'] ?? 'body');
            if ($role === 'accent') {
                $t['color'] = $p['accent'];
            } else {
                $t['color'] = $p['body'];
            }
            $out[] = $t;
        }

        return $out;
    }

    /**
     * @return array{body: string, accent: string}
     */
    private function textPalette(float $bgLuma, string $userAccentHex): array
    {
        $a = $this->normalizeColor($userAccentHex);
        if ($bgLuma < 90.0) {
            return ['body' => '#f4f4f4', 'accent' => $this->lighterAccentForDarkBg($a)];
        }
        if ($bgLuma > 165.0) {
            return ['body' => '#101010', 'accent' => $this->darkerAccentForLightBg($a)];
        }

        return [
            'body' => $bgLuma < 128.0 ? '#fafafa' : '#1a1a1a',
            'accent' => $a,
        ];
    }

    private function lighterAccentForDarkBg(string $hex): string
    {
        if ($this->roughHexLuma($hex) < 150) {
            return '#e8c96a';
        }

        return $hex;
    }

    private function darkerAccentForLightBg(string $hex): string
    {
        if ($this->roughHexLuma($hex) > 200) {
            return '#6b4f1a';
        }

        return $hex;
    }

    private function roughHexLuma(string $hex): float
    {
        $h = ltrim($this->normalizeColor($hex), '#');
        if (strlen($h) < 6) {
            return 128.0;
        }
        $r = hexdec(substr($h, 0, 2));
        $g = hexdec(substr($h, 2, 2));
        $b = hexdec(substr($h, 4, 2));

        return 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{x: int, y: int, w: int, h: int}|null
     */
    private function productRectFromOptionsWhenNoLayer(array $options, int $w, int $h): ?array
    {
        if (isset($options['product_layout_rect']) && is_array($options['product_layout_rect'])) {
            $r = $options['product_layout_rect'];
            if (isset($r['x'], $r['y'], $r['w'], $r['h'])) {
                return [
                    'x' => (int) $r['x'],
                    'y' => (int) $r['y'],
                    'w' => (int) $r['w'],
                    'h' => (int) $r['h'],
                ];
            }
        }
        $path = (string) ($options['product_path_for_layout'] ?? '');
        if ($path !== '' && is_file($path) && is_readable($path)) {
            $scale = (float) ($options['product_scale'] ?? 0.6);
            $bottom = (int) ($options['product_bottom_offset'] ?? 80);

            return CardProductPlacement::rectFromImagePath($path, $w, $h, $scale, $bottom);
        }

        return null;
    }

    /**
     * @return array{tw: int, asc: float, desc: float}
     */
    private function imagickTextMetrics(\Imagick $canvas, string $fontPath, float $size, string $text): array
    {
        $draw = new \ImagickDraw;
        $draw->setFont($fontPath);
        $draw->setFontSize($size);
        $m = $canvas->queryFontMetrics($draw, $text);

        return [
            'tw' => (int) max(1, ceil((float) ($m['textWidth'] ?? 0))),
            'asc' => (float) ($m['ascender'] ?? 0),
            'desc' => (float) ($m['descender'] ?? 0),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @param  array{x: int, y: int, w: int, h: int}|null  $productRect
     * @return array<int, array<string, mixed>>
     */
    private function refineTextsForLocalContrastImagick(
        array $texts,
        \Imagick $canvas,
        ?array $productRect,
        array $options
    ): array {
        if (! $this->optionBool($options, 'local_text_contrast', true) || $texts === []) {
            return $texts;
        }
        $userAccent = (string) ($options['user_accent'] ?? $options['accent_color'] ?? '#d4af37');
        $halo = $this->optionBool($options, 'text_halo_on_light_patch', true);
        $w = (int) $canvas->getImageWidth();
        $h = (int) $canvas->getImageHeight();
        $out = [];
        foreach ($texts as $t) {
            if (! is_array($t) || ! isset($t['text'], $t['x'], $t['y'], $t['size'])) {
                $out[] = $t;

                continue;
            }
            $fontPath = $this->fontPathForLabel((string) ($t['font'] ?? 'Montserrat-Bold.ttf'));
            $textStr = (string) $t['text'];
            $size = (int) $t['size'];
            $met = $this->imagickTextMetrics($canvas, $fontPath, (float) $size, $textStr);
            $align = strtolower((string) ($t['align'] ?? 'left'));
            $x = (int) $t['x'];
            $yTop = (int) $t['y'];
            $tw = $met['tw'];
            $asc = $met['asc'] > 0 ? $met['asc'] : $size * 0.8;
            $th = (int) max(12, ceil($asc + max(0.0, -$met['desc'])));
            $left = $align === 'right' ? $x - $tw : ($align === 'center' ? (int) ($x - $tw / 2) : $x);
            $left = max(0, min($left, max(0, $w - 1)));
            $top = max(0, min($yTop, max(0, $h - 1)));
            $rw = max(1, min($w - $left, $tw + 24));
            $rh = max(1, min($h - $top, $th + 20));
            $localLuma = ImageBrightness::averageLumaFromImagickRegion($canvas, $left, $top, $rw, $rh);
            $adj = $this->remapTextColorsForLuma([$t], $localLuma, $userAccent)[0];
            $overlap = $this->textBandOverlapsProduct($left, $top, $rw, $rh, $productRect);
            $fillLuma = $this->roughHexLuma((string) ($adj['color'] ?? '#ffffff'));
            if ($halo && ($overlap && $localLuma > 100.0 && $fillLuma > 130.0)) {
                $adj['stroke_width'] = max((float) ($adj['stroke_width'] ?? 0), 2.0);
                $adj['stroke_color'] = '#0d0d0d';
            } elseif ($halo && $localLuma > 178.0 && $fillLuma > 165.0) {
                $adj['stroke_width'] = max((float) ($adj['stroke_width'] ?? 0), 1.25);
                $adj['stroke_color'] = '#121212';
            }
            $out[] = $adj;
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @param  array{x: int, y: int, w: int, h: int}|null  $productRect
     * @return array<int, array<string, mixed>>
     */
    private function refineTextsForLocalContrastGd(
        array $texts,
        \GdImage $canvas,
        ?array $productRect,
        array $options
    ): array {
        if (! $this->optionBool($options, 'local_text_contrast', true) || $texts === []) {
            return $texts;
        }
        $userAccent = (string) ($options['user_accent'] ?? $options['accent_color'] ?? '#d4af37');
        $halo = $this->optionBool($options, 'text_halo_on_light_patch', true);
        $w = imagesx($canvas);
        $h = imagesy($canvas);
        $out = [];
        foreach ($texts as $t) {
            if (! is_array($t) || ! isset($t['text'], $t['x'], $t['y'], $t['size'])) {
                $out[] = $t;

                continue;
            }
            $fontPath = $this->fontPathForLabel((string) ($t['font'] ?? 'Montserrat-Bold.ttf'));
            $textStr = (string) $t['text'];
            $size = (int) $t['size'];
            $align = strtolower((string) ($t['align'] ?? 'left'));
            if (function_exists('mb_convert_encoding')) {
                $textStr = (string) mb_convert_encoding($textStr, 'UTF-8', 'UTF-8');
            }
            $box = @imagettfbbox($size, 0, $fontPath, $textStr);
            $tw = is_array($box) ? (int) abs($box[2] - $box[0]) : (int) ($size * max(4, strlen($textStr)) * 0.55);
            $hLine = is_array($box) ? (int) abs($box[7] - $box[1]) : $size;
            if ($hLine < 1) {
                $hLine = $size;
            }
            $x = (int) $t['x'];
            $yTop = (int) $t['y'];
            $left = $align === 'right' ? $x - $tw : ($align === 'center' ? (int) ($x - $tw / 2) : $x);
            $left = max(0, min($left, max(0, $w - 1)));
            $top = max(0, min($yTop, max(0, $h - 1)));
            $rw = max(1, min($w - $left, $tw + 24));
            $rh = max(1, min($h - $top, $hLine + 20));
            $localLuma = ImageBrightness::averageLumaFromGdRegion($canvas, $left, $top, $rw, $rh);
            $adj = $this->remapTextColorsForLuma([$t], $localLuma, $userAccent)[0];
            $overlap = $this->textBandOverlapsProduct($left, $top, $rw, $rh, $productRect);
            $fillLuma = $this->roughHexLuma((string) ($adj['color'] ?? '#ffffff'));
            if ($halo && ($overlap && $localLuma > 100.0 && $fillLuma > 130.0)) {
                $adj['stroke_width'] = max((float) ($adj['stroke_width'] ?? 0), 2.0);
                $adj['stroke_color'] = '#0d0d0d';
            } elseif ($halo && $localLuma > 178.0 && $fillLuma > 165.0) {
                $adj['stroke_width'] = max((float) ($adj['stroke_width'] ?? 0), 1.25);
                $adj['stroke_color'] = '#121212';
            }
            $out[] = $adj;
        }

        return $out;
    }

    /**
     * @param  array{x: int, y: int, w: int, h: int}|null  $productRect
     */
    private function textBandOverlapsProduct(int $tx, int $ty, int $tw, int $th, ?array $productRect): bool
    {
        if ($productRect === null || ! isset($productRect['x'], $productRect['y'], $productRect['w'], $productRect['h'])) {
            return false;
        }
        $m = 8;
        $px = (int) $productRect['x'] - $m;
        $py = (int) $productRect['y'] - $m;
        $pw = (int) $productRect['w'] + 2 * $m;
        $ph = (int) $productRect['h'] + 2 * $m;

        return $tx < $px + $pw && $tx + $tw > $px && $ty < $py + $ph && $ty + $th > $py;
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @param  array{x: int, y: int, w: int, h: int}  $productRect
     */
    private function drawInfographicCalloutsImagick(
        \Imagick $canvas,
        array $texts,
        array $productRect,
        int $w,
        int $h
    ): void {
        $cx = (int) ($productRect['x'] + $productRect['w'] / 2);
        $cy = (int) ($productRect['y'] + $productRect['h'] / 2);
        $rx = max(36, (int) ($productRect['w'] * 0.46));
        $ry = max(36, (int) ($productRect['h'] * 0.40));
        $lineDraw = new \ImagickDraw;
        $lineDraw->setStrokeColor(new \ImagickPixel('rgba(255,255,255,0.62)'));
        $lineDraw->setStrokeWidth(1.35);
        $lineDraw->setFillColor(new \ImagickPixel('none'));
        $dotDraw = new \ImagickDraw;
        $dotDraw->setFillColor(new \ImagickPixel('rgba(255,255,255,0.45)'));
        $dotDraw->setStrokeWidth(0);

        $n = count($texts);
        for ($i = 1; $i < $n; $i++) {
            $t = $texts[$i];
            if (! is_array($t) || ! isset($t['text'], $t['x'], $t['y'], $t['size'])) {
                continue;
            }
            $fontPath = $this->fontPathForLabel((string) ($t['font'] ?? 'Montserrat-Bold.ttf'));
            $met = $this->imagickTextMetrics($canvas, $fontPath, (float) (int) $t['size'], (string) $t['text']);
            $align = strtolower((string) ($t['align'] ?? 'left'));
            $x = (int) $t['x'];
            $yTop = (int) $t['y'];
            $tw = $met['tw'];
            $asc = $met['asc'] > 0 ? $met['asc'] : (int) $t['size'] * 0.8;
            $yMid = (int) ($yTop + min($asc * 0.52, (float) (int) $t['size'] * 0.72));
            if ($align === 'right') {
                $lx = (int) ($x - $tw - 12);
            } elseif ($align === 'center') {
                $lx = (int) ($x - $tw / 2 - 12);
            } else {
                $lx = (int) ($x + $tw + 12);
            }
            $lx = max(4, min($w - 4, $lx));
            $yMid = max(4, min($h - 4, $yMid));
            $slot = $i - 1;
            $isLeft = $x < (int) ($w * 0.52);
            $deg = $isLeft ? 192.0 + $slot * 10.0 : 348.0 - $slot * 11.0;
            $rad = $deg * M_PI / 180.0;
            $ax = (int) round($cx + $rx * cos($rad));
            $ay = (int) round($cy + $ry * sin($rad));
            $ax = max(4, min($w - 4, $ax));
            $ay = max(4, min($h - 4, $ay));
            $lineDraw->line((float) $lx, (float) $yMid, (float) $ax, (float) $ay);
            $dotDraw->ellipse((float) $ax, (float) $ay, 5.0, 5.0, 0, 360);
        }
        $canvas->drawImage($lineDraw);
        $canvas->drawImage($dotDraw);
    }

    /**
     * @param  array<int, array<string, mixed>>  $texts
     * @param  array{x: int, y: int, w: int, h: int}  $productRect
     */
    private function drawInfographicCalloutsGd(
        \GdImage $canvas,
        array $texts,
        array $productRect,
        int $w,
        int $h
    ): void {
        if (! function_exists('imageline') || ! function_exists('imagefilledellipse')) {
            return;
        }
        $cx = (int) ($productRect['x'] + $productRect['w'] / 2);
        $cy = (int) ($productRect['y'] + $productRect['h'] / 2);
        $rx = max(36, (int) ($productRect['w'] * 0.46));
        $ry = max(36, (int) ($productRect['h'] * 0.40));
        $lineCol = imagecolorallocatealpha($canvas, 250, 250, 250, 55);
        $dotCol = imagecolorallocatealpha($canvas, 255, 255, 255, 70);
        $dotEdge = imagecolorallocatealpha($canvas, 255, 255, 255, 40);
        if (! is_int($lineCol) || $lineCol < 0) {
            $lineCol = imagecolorallocate($canvas, 230, 230, 230);
        }
        imagesetthickness($canvas, 2);
        $n = count($texts);
        for ($i = 1; $i < $n; $i++) {
            $t = $texts[$i];
            if (! is_array($t) || ! isset($t['text'], $t['x'], $t['y'], $t['size'])) {
                continue;
            }
            $fontPath = $this->fontPathForLabel((string) ($t['font'] ?? 'Montserrat-Bold.ttf'));
            $textStr = (string) $t['text'];
            $size = (int) $t['size'];
            if (function_exists('mb_convert_encoding')) {
                $textStr = (string) mb_convert_encoding($textStr, 'UTF-8', 'UTF-8');
            }
            $box = @imagettfbbox($size, 0, $fontPath, $textStr);
            $tw = is_array($box) ? (int) abs($box[2] - $box[0]) : (int) ($size * 4);
            $align = strtolower((string) ($t['align'] ?? 'left'));
            $x = (int) $t['x'];
            $yTop = (int) $t['y'];
            $hLine = is_array($box) ? (int) abs($box[7] - $box[1]) : $size;
            if ($hLine < 1) {
                $hLine = $size;
            }
            $asc = $hLine * 0.92;
            $yMid = (int) ($yTop + min($asc * 0.52, $size * 0.72));
            if ($align === 'right') {
                $lx = (int) ($x - $tw - 12);
            } elseif ($align === 'center') {
                $lx = (int) ($x - $tw / 2 - 12);
            } else {
                $lx = (int) ($x + $tw + 12);
            }
            $lx = max(4, min($w - 4, $lx));
            $yMid = max(4, min($h - 4, $yMid));
            $slot = $i - 1;
            $isLeft = $x < (int) ($w * 0.52);
            $deg = $isLeft ? 192.0 + $slot * 10.0 : 348.0 - $slot * 11.0;
            $rad = $deg * M_PI / 180.0;
            $ax = (int) round($cx + $rx * cos($rad));
            $ay = (int) round($cy + $ry * sin($rad));
            $ax = max(4, min($w - 4, $ax));
            $ay = max(4, min($h - 4, $ay));
            imageline($canvas, $lx, $yMid, $ax, $ay, $lineCol);
            imagefilledellipse($canvas, $ax, $ay, 12, 12, is_int($dotCol) && $dotCol >= 0 ? $dotCol : $lineCol);
            imageellipse($canvas, $ax, $ay, 12, 12, is_int($dotEdge) && $dotEdge >= 0 ? $dotEdge : $lineCol);
        }
        imagesetthickness($canvas, 1);
    }
}
