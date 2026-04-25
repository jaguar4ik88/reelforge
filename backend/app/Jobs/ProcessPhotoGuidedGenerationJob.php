<?php

namespace App\Jobs;

use App\Models\GenerationJob;
use App\Services\Credits\CreditService;
use App\Services\Replicate\ReplicateService;
use App\Support\ReelForgeStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessPhotoGuidedGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 900;

    private const POLL_INTERVAL_SEC = 3;
    private const MAX_POLLS         = 50;  // ~150 сек максимум

    public function __construct(private readonly GenerationJob $generationJob) {}

    public function handle(): void
    {
        $job = GenerationJob::query()->findOrFail($this->generationJob->id);

        if (in_array($job->status, ['done', 'failed'], true)) {
            return;
        }

        $job->update(['status' => 'processing']);

        $prompt = $job->final_prompt;

        if (empty(trim($prompt))) {
            throw new RuntimeException('Empty prompt — cannot generate.');
        }

        $quantity = max(1, min(10, (int) ($job->settings_json['quantity'] ?? 1)));
        $contentType = (string) ($job->settings_json['content_type'] ?? 'photo');
        $maxPolls = $contentType === 'video' ? 100 : self::MAX_POLLS;

        /** @var ReplicateService $replicate */
        $replicate        = app(ReplicateService::class);
        $storedPaths      = [];
        $predictionIds    = [];

        for ($index = 0; $index < $quantity; $index++) {
            [$modelId, $input] = $this->buildModelInput($job, $prompt);

            Log::info('ProcessPhotoGuidedGenerationJob: creating Replicate prediction', [
                'generation_job_id' => $job->id,
                'iteration'         => $index + 1,
                'of'                => $quantity,
                'model'             => $modelId,
                'content_type'      => $contentType,
                'prompt_length'     => strlen($prompt),
            ]);

            $prediction = $replicate->createPrediction($modelId, $input);
            $predictionIds[] = $prediction['id'];

            $resultUrl = $this->pollUntilDone($replicate, $prediction['id'], $maxPolls);

            $storedPaths[] = $this->downloadAndStore($resultUrl, $job, $index);
        }

        $primaryPath = $storedPaths[0];

        $job->update([
            'status'        => 'done',
            'provider'      => 'replicate',
            'result_path'   => $primaryPath,
            'settings_json' => array_merge($job->settings_json ?? [], [
                'result_paths'               => $storedPaths,
                'replicate_prediction_ids'   => $predictionIds,
                'replicate_prediction_id'    => $predictionIds[0] ?? null,
            ]),
        ]);

        $job->project()->update([
            'status'     => 'done',
            'video_path' => $primaryPath,
        ]);

        Log::info('ProcessPhotoGuidedGenerationJob: done', [
            'generation_job_id' => $job->id,
            'quantity'          => $quantity,
            'result_paths'      => $storedPaths,
        ]);
    }

    /**
     * Выбирает модель и формирует input для Replicate.
     *
     * Если есть референс-фото → flux-kontext-pro (image-to-image):
     *   товар сохраняется, меняется только сцена/стиль.
     * Без фото → flux-dev/schnell (text-to-image, fallback).
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildModelInput(GenerationJob $job, string $prompt): array
    {
        $contentType = (string) ($job->settings_json['content_type'] ?? 'photo');

        if ($contentType === 'video') {
            return $this->buildVideoImageToVideoInput($job);
        }

        $imageBase64 = $this->loadReferenceImageBase64($job);

        if ($imageBase64 !== null) {
            $modelConfig   = config('prompts.models.kontext');
            $settings      = $job->settings_json ?? [];
            $contentTypeI2I = (string) ($settings['content_type'] ?? 'photo');
            $allowed       = ['9:16', '3:4', '1:1', '4:3', '16:9'];
            $aspectRatio   = $modelConfig['aspect_ratio'];
            if (isset($settings['aspect_ratio']) && in_array($settings['aspect_ratio'], $allowed, true)) {
                $aspectRatio = $settings['aspect_ratio'];
            }
            // Card: upsampling rewrites the prompt and often garbles on-image Cyrillic — keep the exact user prompt.
            $upsampling = $contentTypeI2I === 'card' ? false : (bool) ($modelConfig['prompt_upsampling'] ?? true);

            $input = [
                'prompt'            => $prompt,
                'input_image'       => $imageBase64,
                'aspect_ratio'      => $aspectRatio,
                'output_format'     => $modelConfig['output_format'],
                'safety_tolerance'  => $modelConfig['safety_tolerance'],
                'prompt_upsampling' => $upsampling,
            ];
            if (isset($modelConfig['output_quality'])) {
                $input['output_quality'] = (int) $modelConfig['output_quality'];
            }

            return [
                $modelConfig['id'],
                $input,
            ];
        }

        // Fallback: нет референс-фото → обычная text-to-image генерация
        $contentType = $job->settings_json['content_type'] ?? 'photo';
        $modelKey    = $contentType === 'preview' ? 'preview' : 'default';
        $modelConfig = config("prompts.models.{$modelKey}", config('prompts.models.default'));

        return [
            $modelConfig['id'],
            [
                'prompt'              => $prompt,
                'negative_prompt'     => config('prompts.negative'),
                'width'               => $modelConfig['width'],
                'height'              => $modelConfig['height'],
                'num_inference_steps' => $modelConfig['num_inference_steps'],
                'guidance_scale'      => $modelConfig['guidance_scale'],
            ],
        ];
    }

    /**
     * Short product clip from reference photo (Stable Video Diffusion–class models on Replicate).
     * User "duration" mainly affects pricing; frame count is capped by the model.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildVideoImageToVideoInput(GenerationJob $job): array
    {
        $imageUri = $this->loadReferenceImageBase64($job);
        if ($imageUri === null) {
            throw new RuntimeException('Video generation requires a reference product image.');
        }

        $cfg = config('platform.photo_guided.video_i2v', []);
        $modelId = trim((string) ($cfg['model_id'] ?? ''));
        if ($modelId === '') {
            $modelId = 'aicapcut/stable-video-diffusion-img2vid-xt-optimized:7b595c69ca428904c1907155b93a5580653d1e9dcd407612142595908650dd67';
        }

        $frames = (int) ($cfg['num_frames'] ?? 25);
        $frames = max(14, min(100, $frames));

        $input = [
            'image'               => $imageUri,
            'num_frames'          => $frames,
            'num_inference_steps' => max(1, (int) ($cfg['num_inference_steps'] ?? 25)),
        ];

        $width  = (int) ($cfg['width'] ?? 0);
        $height = (int) ($cfg['height'] ?? 0);
        if ($width > 0) {
            $input['width'] = max(256, $width);
        }
        if ($height > 0) {
            $input['height'] = max(256, $height);
        }

        return [$modelId, $input];
    }

    /**
     * Загружает первое изображение проекта как base64 data URI.
     * Возвращает null, если изображение не найдено или недоступно.
     */
    private function loadReferenceImageBase64(GenerationJob $job): ?string
    {
        $firstImage = $job->project->images()->orderBy('order')->first();

        if ($firstImage === null) {
            return null;
        }

        $disk = ReelForgeStorage::contentDisk();

        if (! Storage::disk($disk)->exists($firstImage->path)) {
            Log::warning('ProcessPhotoGuidedGenerationJob: reference image not found', [
                'path' => $firstImage->path,
            ]);
            return null;
        }

        $bytes     = Storage::disk($disk)->get($firstImage->path);
        $extension = strtolower(pathinfo($firstImage->path, PATHINFO_EXTENSION));
        $mime      = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            default       => 'image/jpeg',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }

    private function pollUntilDone(ReplicateService $replicate, string $predictionId, int $maxPolls): string
    {
        for ($i = 0; $i < $maxPolls; $i++) {
            sleep(self::POLL_INTERVAL_SEC);

            $prediction = $replicate->getPrediction($predictionId);

            Log::debug('ProcessPhotoGuidedGenerationJob: poll', [
                'attempt' => $i + 1,
                'status'  => $prediction['status'],
            ]);

            if ($prediction['status'] === 'succeeded') {
                $output = $prediction['output'];

                // Flux возвращает либо строку либо массив URL
                $url = is_array($output) ? $output[0] : $output;

                if (empty($url)) {
                    throw new RuntimeException('Replicate returned empty output.');
                }

                return is_string($url) ? $url : (string) $url;
            }

            if ($prediction['status'] === 'failed') {
                throw new RuntimeException('Replicate prediction failed: ' . ($prediction['error'] ?? 'unknown error'));
            }
        }

        throw new RuntimeException('Replicate polling timeout after ' . ($maxPolls * self::POLL_INTERVAL_SEC) . ' seconds.');
    }

    private function downloadAndStore(string $mediaUrl, GenerationJob $job, int $index = 0): string
    {
        $response = Http::timeout(180)->get($mediaUrl);

        if ($response->failed()) {
            throw new RuntimeException('Failed to download generated media from: ' . $mediaUrl);
        }

        $disk      = ReelForgeStorage::contentDisk();
        $extension = $this->guessMediaExtension($response, $mediaUrl);
        $path      = ReelForgeStorage::userContentPrefix()
            . "/{$job->user_id}/projects/{$job->project_id}/generated_{$job->id}_{$index}.{$extension}";

        Storage::disk($disk)->put($path, $response->body());

        return $path;
    }

    private function guessMediaExtension(Response $response, string $url): string
    {
        $ct = strtolower((string) $response->header('Content-Type'));
        if (str_contains($ct, 'video/mp4')) {
            return 'mp4';
        }
        if (str_contains($ct, 'video/webm')) {
            return 'webm';
        }
        if (str_contains($ct, 'image/jpeg') || str_contains($ct, 'image/jpg')) {
            return 'jpg';
        }
        if (str_contains($ct, 'image/png')) {
            return 'png';
        }
        if (str_contains($ct, 'image/webp')) {
            return 'webp';
        }

        $path  = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $guess = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if ($guess === 'jpeg') {
            return 'jpg';
        }
        if (in_array($guess, ['mp4', 'jpg', 'png', 'webp', 'webm'], true)) {
            return $guess;
        }

        return 'bin';
    }

    public function failed(Throwable $exception): void
    {
        $fresh = $this->generationJob->fresh();
        if ($fresh === null) {
            return;
        }

        Log::error('ProcessPhotoGuidedGenerationJob: failed', [
            'generation_job_id' => $fresh->id,
            'error'             => $exception->getMessage(),
        ]);

        app(CreditService::class)->refundFailedPhotoGuidedGeneration($fresh);

        $fresh->update([
            'status'        => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        $fresh->project()->update(['status' => 'failed']);
    }
}
