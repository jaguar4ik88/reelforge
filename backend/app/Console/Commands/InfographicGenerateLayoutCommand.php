<?php

namespace App\Console\Commands;

use App\Services\Infographic\InfographicTemplateLayoutGenerator;
use Illuminate\Console\Command;

class InfographicGenerateLayoutCommand extends Command
{
    protected $signature = 'infographic:generate-layout
                            {image : Image basename in storage/app/public/infographic (e.g. shoes-1.jpg) or absolute path inside that directory}
                            {--key= : Manifest key in templates-layout.json (default: image basename)}
                            {--name= : Label sent to the vision model (default: derived from name)}
                            {--dry-run : Write proposed JSON to stdout only}
                            {--force : Replace existing manifest entry without prompt}';

    protected $description = 'Analyse an infographic background image with OpenAI Vision and merge editable text slots into templates-layout.json';

    public function handle(InfographicTemplateLayoutGenerator $generator): int
    {
        $input = (string) $this->argument('image');
        $resolved = $generator->resolveInfographicImagePath($input);

        if ($resolved === null) {
            $this->error('Image not found or not allowed. Use a JPG/PNG/WebP in storage/app/public/infographic/');

            return self::FAILURE;
        }

        $manifestKey = $this->option('key') ? basename((string) $this->option('key')) : basename($resolved);

        $layoutPath = dirname($resolved).DIRECTORY_SEPARATOR.'templates-layout.json';
        $existing = [];
        if (is_readable($layoutPath)) {
            $decoded = json_decode((string) file_get_contents($layoutPath), true);
            $existing = is_array($decoded) ? $decoded : [];
        }
        if (isset($existing[$manifestKey]) && ! $this->option('force') && ! $this->option('dry-run')) {
            if (! $this->confirm("Entry \"{$manifestKey}\" already exists in templates-layout.json. Overwrite?", false)) {
                return self::SUCCESS;
            }
        }

        $label = $this->option('name') ? (string) $this->option('name') : null;

        $this->info("Analysing: {$resolved}");
        $this->line("Manifest key: {$manifestKey}");
        $this->newLine();

        $result = $generator->generate(
            $resolved,
            $manifestKey,
            $label,
            overwriteExisting: true,
            dryRun: (bool) $this->option('dry-run'),
        );

        if (! $result['ok']) {
            $this->error($result['message']);
            if (isset($result['vision']) && is_array($result['vision'])) {
                $this->line(json_encode($result['vision'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return self::FAILURE;
        }

        if ($this->option('dry-run') && isset($result['entry'])) {
            $this->line(json_encode([$manifestKey => $result['entry']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Merged into templates-layout.json: '.$manifestKey);
        $this->table(['Layers (texts)'], [[$result['texts_count'] ?? 0]]);

        return self::SUCCESS;
    }
}
