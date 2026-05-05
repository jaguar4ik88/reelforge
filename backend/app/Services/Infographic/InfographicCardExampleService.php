<?php

namespace App\Services\Infographic;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

/**
 * Reference card images for Product card (AI by example) — public/storage/cards/exemple.
 */
class InfographicCardExampleService
{
    public function examplesDirectory(): string
    {
        return base_path('public/storage/cards/exemple');
    }

    /**
     * @return list<array{filename: string, url: string}>
     */
    public function listForApi(): array
    {
        $dir = $this->examplesDirectory();
        if (! File::isDirectory($dir)) {
            return [];
        }

        $files = array_values(array_unique(array_merge(
            File::glob($dir.'/*.jpg') ?: [],
            File::glob($dir.'/*.jpeg') ?: [],
            File::glob($dir.'/*.png') ?: [],
            File::glob($dir.'/*.webp') ?: [],
            File::glob($dir.'/*.JPG') ?: [],
            File::glob($dir.'/*.PNG') ?: [],
            File::glob($dir.'/*.WEBP') ?: [],
        )));
        sort($files);
        $out = [];
        foreach ($files as $full) {
            if (! is_string($full) || ! is_file($full)) {
                continue;
            }
            $name = basename($full);
            if (! $this->isAllowedBasename($name)) {
                continue;
            }
            $out[] = [
                'filename' => $name,
                'url' => URL::asset('storage/cards/exemple/'.$name),
            ];
        }

        return $out;
    }

    public function resolvePublicExamplePath(string $filename): ?string
    {
        $name = basename($filename);
        if (! $this->isAllowedBasename($name)) {
            return null;
        }
        $full = $this->examplesDirectory().DIRECTORY_SEPARATOR.$name;
        if (! is_file($full)) {
            return null;
        }

        return $full;
    }

    public function isAllowedBasename(string $name): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9._-]+\.(jpe?g|png|webp)$/i', $name);
    }
}
