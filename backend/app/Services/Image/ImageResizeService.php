<?php

namespace App\Services\Image;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Downscales uploaded raster images so the long edge is at most {@see $maxDimension}px.
 * Output is JPEG (quality 85) for smaller files; skipped if GD is missing or image is already small enough.
 */
class ImageResizeService
{
    private readonly int $maxDimension;

    public function __construct(?int $maxDimension = null)
    {
        $this->maxDimension = $maxDimension ?? (int) config('platform.image_max_dimension', 1024);
    }

    /**
     * Returns a new temporary UploadedFile (JPEG) or the original file if resize is not needed / not possible.
     */
    public function resizeUploadedFileIfNeeded(UploadedFile $file): UploadedFile
    {
        if (! extension_loaded('gd')) {
            return $file;
        }

        $path = $file->getRealPath();
        if ($path === false || ! is_readable($path)) {
            return $file;
        }

        $binary = $this->resizeToJpegMaxEdge($path);
        if ($binary === null) {
            return $file;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'rf_img_');
        if ($tmp === false) {
            return $file;
        }

        file_put_contents($tmp, $binary);

        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';

        return new UploadedFile(
            $tmp,
            $base.'-'.$this->maxDimension.'.jpg',
            'image/jpeg',
            null,
            true
        );
    }

    /**
     * @return string|null JPEG binary, or null to keep original file
     */
    private function resizeToJpegMaxEdge(string $path): ?string
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        $src = @imagecreatefromstring($data);
        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $max = $this->maxDimension;

        if ($w <= 0 || $h <= 0) {
            imagedestroy($src);

            return null;
        }

        if ($w <= $max && $h <= $max) {
            imagedestroy($src);

            return null;
        }

        $scale = $max / max($w, $h);
        $nw    = max(1, (int) round($w * $scale));
        $nh    = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        if ($dst === false) {
            imagedestroy($src);

            return null;
        }

        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        ob_start();
        imagejpeg($dst, null, 85);
        imagedestroy($dst);
        $out = ob_get_clean();

        return $out !== false && $out !== '' ? $out : null;
    }
}
