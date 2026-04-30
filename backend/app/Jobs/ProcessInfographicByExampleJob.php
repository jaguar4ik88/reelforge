<?php

namespace App\Jobs;

use App\Models\GenerationJob;
use App\Services\Credits\CreditService;
use App\Services\Infographic\InfographicByExampleOpenAiSize;
use App\Services\Infographic\InfographicByExamplePromptBuilder;
use App\Services\OpenAI\GptImageProductCardService;
use App\Services\Vision\ProductCardPhotoAnalysisService;
use App\Support\ReelForgeStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessInfographicByExampleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(private readonly GenerationJob $generationJob) {}

    public function handle(
        ProductCardPhotoAnalysisService $vision,
        InfographicByExamplePromptBuilder $promptBuilder,
        GptImageProductCardService $gptImage,
    ): void {
        $job = GenerationJob::query()->findOrFail($this->generationJob->id);

        if (in_array($job->status, ['done', 'failed'], true)) {
            return;
        }

        $job->update(['status' => 'processing']);

        $settings = $job->settings_json ?? [];
        $project = $job->project;
        if ($project === null) {
            throw new RuntimeException('Generation job has no project.');
        }

        $firstImage = $project->images()->orderBy('order')->first();
        if ($firstImage === null) {
            throw new RuntimeException('Project has no product image.');
        }

        $disk = ReelForgeStorage::contentDisk();
        if (! Storage::disk($disk)->exists($firstImage->path)) {
            throw new RuntimeException('Product image not found on disk.');
        }
        $productBytes = Storage::disk($disk)->get($firstImage->path);

        $exampleBytes = $this->loadExampleBytes($settings);
        if ($exampleBytes === null || $exampleBytes === '') {
            throw new RuntimeException('Example card image could not be loaded.');
        }

        $productAnalysis = $vision->analyzeRawBytes($productBytes, 'jpg', 'infographic_product_analysis_system') ?? [];
        $exampleAnalysis = $vision->analyzeRawBytes($exampleBytes, 'jpg', 'infographic_example_card_analysis_system') ?? [];

        $title = trim((string) ($settings['infographic_title'] ?? $project->title));
        $lines = $this->characteristicLines((string) ($settings['infographic_characteristics'] ?? ''));
        $aspectUi = (string) ($settings['aspect_ratio_ui'] ?? '1:1');

        [$openAiSize, $canvasLabel] = InfographicByExampleOpenAiSize::fromAspectUi($aspectUi);

        $prompt = $promptBuilder->build(
            $productAnalysis,
            $exampleAnalysis,
            $title,
            $lines,
            $aspectUi,
            $canvasLabel,
            $openAiSize,
        );

        $productMime = $this->guessMime($productBytes, $firstImage->path);
        $exampleMime = $this->guessExampleMime($exampleBytes, $settings);

        Log::info('ProcessInfographicByExampleJob: GPT Image product card', [
            'generation_job_id' => $job->id,
            'size' => $openAiSize,
            'aspect_ui' => $aspectUi,
        ]);

        $imageBinary = $gptImage->generateProductCard(
            $prompt,
            $productBytes,
            $productMime,
            $exampleBytes,
            $exampleMime,
            $openAiSize,
        );

        $storedPath = $this->storeRawImage($imageBinary, $job, 'png');

        $meta = is_array($project->product_meta_json) ? $project->product_meta_json : [];
        $meta['infographic_by_example'] = [
            'product_analysis' => $productAnalysis,
            'example_analysis' => $exampleAnalysis,
            'aspect_ratio_ui' => $aspectUi,
            'openai_size' => $openAiSize,
            'image_model' => (string) config('services.openai.gpt_image_model', 'gpt-image-1.5'),
        ];

        $job->update([
            'status' => 'done',
            'provider' => 'openai',
            'result_path' => $storedPath,
            'final_prompt' => $prompt,
            'settings_json' => array_merge($settings, [
                'gpt_image_pipeline' => 'images_edits',
            ]),
        ]);

        $project->update([
            'status' => 'done',
            'video_path' => $storedPath,
            'product_meta_json' => $meta,
        ]);

        $this->cleanupUploadedExample($settings);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function loadExampleBytes(array $settings): ?string
    {
        $source = (string) ($settings['example_source'] ?? '');
        if ($source === 'gallery') {
            $path = (string) ($settings['example_public_path'] ?? '');
            if ($path === '' || ! is_file($path)) {
                return null;
            }
            $raw = @file_get_contents($path);

            return is_string($raw) ? $raw : null;
        }
        if ($source === 'upload') {
            $rel = (string) ($settings['example_upload_path'] ?? '');
            if ($rel === '') {
                return null;
            }
            if (! Storage::disk('local')->exists($rel)) {
                return null;
            }

            return Storage::disk('local')->get($rel);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function cleanupUploadedExample(array $settings): void
    {
        if (($settings['example_source'] ?? '') !== 'upload') {
            return;
        }
        $rel = (string) ($settings['example_upload_path'] ?? '');
        if ($rel !== '' && Storage::disk('local')->exists($rel)) {
            Storage::disk('local')->delete($rel);
        }
    }

    private function characteristicLines(string $raw): array
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $raw = str_replace("\r", "\n", $raw);
        $lines = explode("\n", $raw);
        $out = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return $out;
    }

    private function storeRawImage(string $binary, GenerationJob $job, string $extension): string
    {
        $disk = ReelForgeStorage::contentDisk();
        $ext = $extension === 'png' ? 'png' : 'jpg';
        $path = ReelForgeStorage::userContentPrefix()
            ."/{$job->user_id}/projects/{$job->project_id}/generated_{$job->id}_0.{$ext}";
        Storage::disk($disk)->put($path, $binary);

        return $path;
    }

    private function guessMime(string $bytes, string $pathHint): string
    {
        $ext = strtolower((string) pathinfo($pathHint, PATHINFO_EXTENSION));
        $byExt = $this->mimeFromExtension($ext);
        if ($byExt !== null) {
            return $byExt;
        }
        if (function_exists('finfo_open')) {
            $f = @finfo_open(FILEINFO_MIME_TYPE);
            if ($f !== false) {
                $m = finfo_buffer($f, $bytes) ?: '';
                finfo_close($f);
                if (is_string($m) && str_starts_with(strtolower($m), 'image/')) {
                    return strtolower($m);
                }
            }
        }

        return 'image/jpeg';
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function guessExampleMime(string $bytes, array $settings): string
    {
        $pathHint = '';
        if (($settings['example_source'] ?? '') === 'gallery') {
            $pathHint = (string) ($settings['example_public_path'] ?? '');
        } elseif (($settings['example_source'] ?? '') === 'upload') {
            $pathHint = (string) ($settings['example_upload_path'] ?? '');
        }

        return $this->guessMime($bytes, $pathHint);
    }

    private function mimeFromExtension(string $ext): ?string
    {
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => null,
        };
    }

    public function failed(Throwable $exception): void
    {
        $fresh = $this->generationJob->fresh();
        if ($fresh === null) {
            return;
        }

        Log::error('ProcessInfographicByExampleJob: failed', [
            'generation_job_id' => $fresh->id,
            'error' => $exception->getMessage(),
        ]);

        app(CreditService::class)->refundFailedPhotoGuidedGeneration($fresh);

        $settings = $fresh->settings_json ?? [];
        $this->cleanupUploadedExample(is_array($settings) ? $settings : []);

        $fresh->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
        $fresh->project()?->update(['status' => 'failed']);
    }
}
