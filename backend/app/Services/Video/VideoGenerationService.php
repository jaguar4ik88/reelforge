<?php

namespace App\Services\Video;

use App\DTO\VideoGenerationDTO;
use App\Support\ReelForgeStorage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoGenerationService
{
    private const WIDTH  = 1080;
    private const HEIGHT = 1920;
    private const FPS    = 30;
    private const SLIDE_DURATION = 3;

    public function generate(VideoGenerationDTO $dto): string
    {
        $tempDir   = sys_get_temp_dir() . '/reelforge_' . $dto->projectId . '_' . Str::random(8);
        mkdir($tempDir, 0755, true);

        try {
            $localImages = $this->downloadImages($dto->imagePaths, $tempDir);
            $outputPath  = $tempDir . '/output.mp4';

            $this->runFfmpeg($localImages, $outputPath, $dto);

            $disk = ReelForgeStorage::contentDisk();
            $key  = ReelForgeStorage::projectVideoRelativePath($dto->userId, $dto->projectId);
            Storage::disk($disk)->put($key, fopen($outputPath, 'r'));

            return $key;
        } finally {
            $this->cleanup($tempDir);
        }
    }

    private function downloadImages(array $paths, string $tempDir): array
    {
        $local = [];
        $disk = ReelForgeStorage::contentDisk();
        foreach ($paths as $i => $storedPath) {
            $localPath = "{$tempDir}/img_{$i}.jpg";
            file_put_contents($localPath, Storage::disk($disk)->get($storedPath));
            $local[] = $localPath;
        }
        return $local;
    }

    private function runFfmpeg(array $images, string $outputPath, VideoGenerationDTO $dto): void
    {
        $config   = $dto->templateConfig;
        $count    = count($images);
        $ffmpeg   = config('reelforge.ffmpeg_binaries', '/usr/bin/ffmpeg');

        $duration  = self::SLIDE_DURATION;
        $fontColor = $config['font_color']  ?? 'white';
        $overlayBg = $config['overlay_color'] ?? '0x000000@0.45';
        $fontFile  = config('reelforge.ffmpeg_font_path', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf');

        // drawtext requires libfreetype compiled into ffmpeg.
        // Fall back to slideshow-only if font file is missing.
        $withText = file_exists($fontFile);

        if (! $withText) {
            Log::warning("ffmpeg font not found at [{$fontFile}] — generating slideshow without text overlay.");
        }

        // Build input arguments: one -loop/-t/-i per image
        $inputs = '';
        for ($i = 0; $i < $count; $i++) {
            $inputs .= " -loop 1 -t {$duration} -i " . escapeshellarg($images[$i]);
        }

        // Build filter_complex
        $filterParts = [];
        for ($i = 0; $i < $count; $i++) {
            $filterParts[] = "[{$i}:v]scale=" . self::WIDTH . ":" . self::HEIGHT .
                ":force_original_aspect_ratio=decrease," .
                "pad=" . self::WIDTH . ":" . self::HEIGHT . ":(ow-iw)/2:(oh-ih)/2," .
                "setsar=1[scaled{$i}]";
        }

        // Concat all slides
        $concatInputs = implode('', array_map(fn($i) => "[scaled{$i}]", range(0, $count - 1)));
        $filterParts[] = "{$concatInputs}concat=n={$count}:v=1:a=0[concat_v]";

        if ($withText) {
            $overlayH      = 320;
            $overlayY      = self::HEIGHT - $overlayH;
            $titleFontSize = 52;
            $priceFontSize = 68;
            $descFontSize  = 34;

            $title       = $this->escapeFfmpegText($dto->title);
            $price       = $this->escapeFfmpegText('$' . number_format((float) $dto->price, 2));
            $description = $this->escapeFfmpegText(mb_substr($dto->description, 0, 80));

            $filterParts[] = "[concat_v]drawbox=x=0:y={$overlayY}:w=" . self::WIDTH . ":h={$overlayH}:color={$overlayBg}:t=fill[boxed]";
            $filterParts[] = "[boxed]drawtext=fontfile={$fontFile}:text='{$title}':fontcolor={$fontColor}:fontsize={$titleFontSize}:x=(w-text_w)/2:y=" . ($overlayY + 20) . "[titled]";
            $filterParts[] = "[titled]drawtext=fontfile={$fontFile}:text='{$price}':fontcolor=0xFFD700:fontsize={$priceFontSize}:x=(w-text_w)/2:y=" . ($overlayY + 95) . "[priced]";
            $filterParts[] = "[priced]drawtext=fontfile={$fontFile}:text='{$description}':fontcolor={$fontColor}:fontsize={$descFontSize}:x=(w-text_w)/2:y=" . ($overlayY + 190) . ":line_spacing=8[final]";
        } else {
            // No drawtext — just rename the concat output to [final]
            $filterParts[array_key_last($filterParts)] = str_replace(
                '[concat_v]',
                '[concat_v]',
                $filterParts[array_key_last($filterParts)]
            );
            $filterParts[] = "[concat_v]null[final]";
        }

        $filterComplex = implode('; ', $filterParts);

        $cmd = "{$ffmpeg} -y {$inputs} "
            . "-filter_complex " . escapeshellarg($filterComplex) . " "
            . "-map '[final]' "
            . "-c:v libx264 -preset fast -crf 23 "
            . "-r " . self::FPS . " -pix_fmt yuv420p "
            . "-movflags +faststart "
            . escapeshellarg($outputPath)
            . " 2>&1";

        Log::info("Running ffmpeg for project {$dto->projectId}", ['cmd' => $cmd]);

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            $error = implode("\n", $output);
            Log::error("ffmpeg failed for project {$dto->projectId}", ['output' => $error]);
            throw new \RuntimeException("ffmpeg failed: {$error}");
        }
    }

    private function escapeFfmpegText(string $text): string
    {
        return str_replace(
            ["'", ':', '\\', '[', ']'],
            ["\\'", '\\:', '\\\\', '\\[', '\\]'],
            $text
        );
    }

    private function cleanup(string $dir): void
    {
        if (is_dir($dir)) {
            array_map('unlink', glob("{$dir}/*"));
            rmdir($dir);
        }
    }
}
