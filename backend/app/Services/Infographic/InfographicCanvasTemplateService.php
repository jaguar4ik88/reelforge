<?php

namespace App\Services\Infographic;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Canvas templates: files in storage/app/public/infographic (root only).
 * Optional templates-layout.json maps basename → editable text slots.
 */
class InfographicCanvasTemplateService
{
    private const MANIFEST_BASENAME = 'templates-layout.json';

    public function templatesDirectory(): string
    {
        return storage_path('app/public/infographic');
    }

    /**
     * @return array<string, array{texts: list<array<string, mixed>>}>
     */
    private function editorManifestByBasename(): array
    {
        $path = $this->templatesDirectory().DIRECTORY_SEPARATOR.self::MANIFEST_BASENAME;
        if (! is_readable($path)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $key => $payload) {
            if (! is_string($key)) {
                continue;
            }
            $name = basename(str_replace('\\', '/', $key));
            if ($name === self::MANIFEST_BASENAME || $name === 'templates-layout.example.json') {
                continue;
            }
            if (! $this->isAllowedBasename($name)) {
                continue;
            }
            $sanitized = $this->sanitizeEditorPayload($payload);
            if ($sanitized !== null) {
                $out[$name] = $sanitized;
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $raw
     * @return array{texts: list<array<string, mixed>>}|null
     */
    private function sanitizeEditorPayload(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }
        if (! isset($raw['texts']) || ! is_array($raw['texts'])) {
            return null;
        }
        $texts = [];
        foreach ($raw['texts'] as $row) {
            if (! is_array($row) || count($texts) >= 24) {
                continue;
            }
            $content = array_key_exists('content', $row) ? (string) $row['content'] : '';
            if (strlen($content) > 4000) {
                $content = substr($content, 0, 4000);
            }
            $leftRatio = isset($row['leftRatio']) ? (float) $row['leftRatio'] : 0.08;
            $topRatio = isset($row['topRatio']) ? (float) $row['topRatio'] : 0.08;
            $fontSize = isset($row['fontSize']) ? (float) $row['fontSize'] : 16.0;
            $widthRatio = isset($row['widthRatio']) ? (float) $row['widthRatio'] : 0.0;
            $leftRatio = max(0.0, min(1.0, $leftRatio));
            $topRatio = max(0.0, min(1.0, $topRatio));
            $fontSize = max(8.0, min(160.0, $fontSize));
            $widthRatio = max(0.0, min(1.0, $widthRatio));
            $fill = isset($row['fill']) && is_string($row['fill']) ? substr($row['fill'], 0, 32) : '#ffffff';
            $fontWeight = isset($row['fontWeight']) ? (string) $row['fontWeight'] : '400';
            $fontWeight = substr(preg_replace('/[^0-9a-z]/i', '', $fontWeight) ?? '400', 0, 8) ?: '400';
            $fontFamily = isset($row['fontFamily']) && is_string($row['fontFamily'])
                ? substr($row['fontFamily'], 0, 120)
                : 'system-ui, -apple-system, Segoe UI, sans-serif';
            $kind = isset($row['kind']) && is_string($row['kind']) ? strtolower(substr($row['kind'], 0, 16)) : '';
            if ($kind !== 'textbox' && $kind !== '') {
                $kind = '';
            }
            if ($widthRatio > 0 && $kind === '') {
                $kind = 'textbox';
            }
            $shadow = ! isset($row['shadow']) || filter_var($row['shadow'], FILTER_VALIDATE_BOOLEAN);

            $texts[] = [
                'content' => $content,
                'leftRatio' => $leftRatio,
                'topRatio' => $topRatio,
                'fontSize' => $fontSize,
                'fill' => $fill,
                'fontWeight' => $fontWeight,
                'fontFamily' => $fontFamily,
                'shadow' => $shadow,
                'widthRatio' => $widthRatio,
                'kind' => $kind,
            ];
        }

        if ($texts === []) {
            return null;
        }

        return ['texts' => $texts];
    }

    /**
     * @return list<array{filename: string, url: string, editor?: array{texts: list<array<string, mixed>>}}>
     */
    public function listForApi(): array
    {
        $dir = $this->templatesDirectory();
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
        $manifest = $this->editorManifestByBasename();
        $out = [];
        foreach ($files as $full) {
            if (! is_string($full) || ! is_file($full)) {
                continue;
            }
            $name = basename($full);
            if (! $this->isAllowedBasename($name)) {
                continue;
            }
            $item = [
                'filename' => $name,
                'url' => Storage::disk('public')->url('infographic/'.$name),
            ];
            if (isset($manifest[$name])) {
                $item['editor'] = $manifest[$name];
            }
            $out[] = $item;
        }

        return $out;
    }

    public function isAllowedBasename(string $name): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9._-]+\.(jpe?g|png|webp)$/i', $name);
    }
}
