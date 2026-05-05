<?php

namespace App\Services\Infographic;

use Illuminate\Support\Str;

/**
 * OpenAI Vision → templates-layout.json merge. Used by Artisan command and admin API.
 */
class InfographicTemplateLayoutGenerator
{
    public function __construct(
        private readonly InfographicCanvasTemplateService $canvasTemplates,
        private readonly InfographicTemplateVisionAnalyzer $analyzer,
        private readonly InfographicVisionLayoutConverter $converter,
    ) {}

    /**
     * @return array{ok: bool, message: string, manifest_key?: string, texts_count?: int, entry?: array{texts: array}, vision?: array}
     */
    public function generate(
        string $absolutePath,
        string $manifestKey,
        ?string $visionLabel = null,
        bool $overwriteExisting = true,
        bool $dryRun = false,
    ): array {
        if (! $this->canvasTemplates->isAllowedBasename($manifestKey)) {
            return ['ok' => false, 'message' => 'Invalid manifest filename.'];
        }

        if (! is_readable($absolutePath)) {
            return ['ok' => false, 'message' => 'Image file is not readable.'];
        }

        $dirReal = realpath($this->canvasTemplates->templatesDirectory()) ?: $this->canvasTemplates->templatesDirectory();
        $pathReal = realpath($absolutePath);
        if ($pathReal === false || ! str_starts_with($pathReal, $dirReal)) {
            return ['ok' => false, 'message' => 'Image must live under the infographic storage directory.'];
        }

        $layoutPath = $this->canvasTemplates->templatesDirectory().DIRECTORY_SEPARATOR.'templates-layout.json';
        $existing = $this->readManifest($layoutPath);

        if (isset($existing[$manifestKey]) && ! $overwriteExisting) {
            return [
                'ok' => false,
                'code' => 'manifest_exists',
                'message' => 'Manifest entry already exists. Send force=true to replace.',
                'manifest_key' => $manifestKey,
            ];
        }

        $label = $visionLabel ?? Str::title(str_replace(['-', '_'], ' ', pathinfo($manifestKey, PATHINFO_FILENAME)));

        $vision = $this->analyzer->analyse($absolutePath, $label);
        if ($vision === null) {
            return [
                'ok' => false,
                'message' => 'Vision analysis failed. Check OPENAI_API_KEY and logs.',
            ];
        }

        $entry = $this->converter->toManifestEntry($vision);
        if ($entry === null) {
            return [
                'ok' => false,
                'message' => 'No editable text layers detected in the vision response.',
                'vision' => $vision,
            ];
        }

        if ($dryRun) {
            return [
                'ok' => true,
                'message' => 'Dry run.',
                'manifest_key' => $manifestKey,
                'texts_count' => count($entry['texts']),
                'entry' => $entry,
            ];
        }

        $existing[$manifestKey] = $entry;
        ksort($existing);
        file_put_contents(
            $layoutPath,
            json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n"
        );

        return [
            'ok' => true,
            'message' => '',
            'manifest_key' => $manifestKey,
            'texts_count' => count($entry['texts']),
        ];
    }

    public function resolveInfographicImagePath(string $input): ?string
    {
        $dir = $this->canvasTemplates->templatesDirectory();
        $dirReal = realpath($dir) ?: $dir;

        if (str_starts_with($input, '/')) {
            $path = realpath($input);
            if ($path === false || ! str_starts_with($path, $dirReal)) {
                return null;
            }
            $base = basename($path);

            return $this->canvasTemplates->isAllowedBasename($base) ? $path : null;
        }

        $base = basename(str_replace('\\', '/', $input));
        if (! $this->canvasTemplates->isAllowedBasename($base)) {
            return null;
        }

        $full = $dir.DIRECTORY_SEPARATOR.$base;

        return is_file($full) ? $full : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $layoutPath): array
    {
        if (! is_readable($layoutPath)) {
            return [];
        }
        $decoded = json_decode((string) file_get_contents($layoutPath), true);

        return is_array($decoded) ? $decoded : [];
    }
}
