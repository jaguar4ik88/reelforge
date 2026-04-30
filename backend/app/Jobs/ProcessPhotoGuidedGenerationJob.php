<?php

namespace App\Jobs;

use App\Models\GenerationJob;
use App\Models\Project;
use App\Services\Card\CardComposerService;
use App\Services\Card\CardInfographicTextLayout;
use App\Services\Card\CardOutputDimensions;
use App\Services\Card\CardProductPlacement;
use App\Services\Card\CardPuppeteerRendererService;
use App\Services\Card\ImageBrightness;
use App\Services\Credits\CreditService;
use App\Services\Product\ProductPromptBuilder;
use App\Services\Replicate\ReplicateService;
use App\Services\Vision\ProductCardPhotoAnalysisService;
use App\Support\ReelForgeStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessPhotoGuidedGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 900;

    private const POLL_INTERVAL_SEC = 3;

    private const MAX_POLLS = 50;  // ~150 сек максимум

    public function __construct(private readonly GenerationJob $generationJob) {}

    public function handle(): void
    {
        $job = GenerationJob::query()->findOrFail($this->generationJob->id);

        if (in_array($job->status, ['done', 'failed'], true)) {
            return;
        }

        $job->update(['status' => 'processing']);

        $settings = $job->settings_json ?? [];
        if (
            (string) ($settings['content_type'] ?? '') === 'card'
            && filter_var(config('platform.card.php_composite.enabled', false), FILTER_VALIDATE_BOOLEAN)
        ) {
            $this->processCardWithPhpComposite($job);

            return;
        }

        $prompt = $job->final_prompt;

        if (empty(trim($prompt))) {
            throw new RuntimeException('Empty prompt — cannot generate.');
        }

        $quantity = max(1, min(10, (int) ($job->settings_json['quantity'] ?? 1)));
        $contentType = (string) ($job->settings_json['content_type'] ?? 'photo');
        $maxPolls = $contentType === 'video' ? 100 : self::MAX_POLLS;

        /** @var ReplicateService $replicate */
        $replicate = app(ReplicateService::class);
        $storedPaths = [];
        $predictionIds = [];

        for ($index = 0; $index < $quantity; $index++) {
            [$modelId, $input] = $this->buildModelInput($job, $prompt);

            Log::info('ProcessPhotoGuidedGenerationJob: creating Replicate prediction', [
                'generation_job_id' => $job->id,
                'iteration' => $index + 1,
                'of' => $quantity,
                'model' => $modelId,
                'content_type' => $contentType,
                'prompt_length' => strlen($prompt),
            ]);

            $prediction = $replicate->createPrediction($modelId, $input);
            $predictionIds[] = $prediction['id'];

            $resultUrl = $this->pollUntilDone($replicate, $prediction['id'], $maxPolls);

            $storedPaths[] = $this->downloadAndStore($resultUrl, $job, $index);
        }

        $primaryPath = $storedPaths[0];

        $job->update([
            'status' => 'done',
            'provider' => 'replicate',
            'result_path' => $primaryPath,
            'settings_json' => array_merge($job->settings_json ?? [], [
                'result_paths' => $storedPaths,
                'replicate_prediction_ids' => $predictionIds,
                'replicate_prediction_id' => $predictionIds[0] ?? null,
            ]),
        ]);

        $job->project()->update([
            'status' => 'done',
            'video_path' => $primaryPath,
        ]);

        Log::info('ProcessPhotoGuidedGenerationJob: done', [
            'generation_job_id' => $job->id,
            'quantity' => $quantity,
            'result_paths' => $storedPaths,
        ]);
    }

    /**
     * Card PHP composite: either (1) Kontext scene regen + vision typography + text overlay, or
     * (2) legacy T2I empty backdrop + optional product paste + text.
     */
    private function processCardWithPhpComposite(GenerationJob $job): void
    {
        if (filter_var(config('platform.card.php_composite.regen_scene_before_text', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->processCardSceneRegenThenOverlay($job);

            return;
        }

        $this->processCardLegacyT2IAndProductPaste($job);
    }

    /**
     * Replicate FLUX Kontext → full scene with solo product, then Claude/OpenAI vision for accent + product box, then PHP text.
     */
    private function processCardSceneRegenThenOverlay(GenerationJob $job): void
    {
        /** @var ReplicateService $replicate */
        $replicate = app(ReplicateService::class);
        /** @var ProductCardPhotoAnalysisService $vision */
        $vision = app(ProductCardPhotoAnalysisService::class);
        $composer = CardComposerService::fromConfig();
        $builder = app(ProductPromptBuilder::class);
        $settings = $job->settings_json ?? [];

        $dataUri = $this->loadReferenceImageBase64($job);
        if ($dataUri === null) {
            throw new RuntimeException('Card scene regen requires a reference product image.');
        }

        $refAnalysis = $vision->analyze($job->project);

        $allowed = ['9:16', '3:4', '1:1', '4:3', '16:9'];
        $aspectRatio = (string) ($settings['aspect_ratio'] ?? '1:1');
        if (! in_array($aspectRatio, $allowed, true)) {
            $aspectRatio = '1:1';
        }

        $modelKey = (string) config('platform.card.php_composite.scene_regen_model_key', 'kontext');
        $modelConfig = config("prompts.models.{$modelKey}", config('prompts.models.kontext'));

        $longEdge = (int) config('platform.card.php_composite.long_edge', 1080);
        [$w, $h] = CardOutputDimensions::pixelsForAspect($aspectRatio, $longEdge);

        $enriched = (string) ($settings['_enriched_wishes'] ?? '');
        $regenPrompt = $builder->buildCardSceneRegenPrompt(
            $enriched,
            (string) ($job->image_caption ?? ''),
            $refAnalysis
        );

        $lines = $this->cardWishLines($settings);
        if ($lines === []) {
            $lines = $this->defaultCardCopyLines($job);
            Log::info('ProcessPhotoGuidedGenerationJob: card user_wishes empty, overlay uses product name/title', [
                'generation_job_id' => $job->id,
            ]);
        }

        $fontBold = (string) config('platform.card.php_composite.font_bold', 'Montserrat-Bold.ttf');
        $fontReg = (string) config('platform.card.php_composite.font_regular', 'Montserrat-Regular.ttf');
        $baseAccent = (string) ($settings['card_accent_color'] ?? config('platform.card.php_composite.default_accent', '#d4af37'));
        $productScale = (float) ($settings['card_product_scale'] ?? 0.58);
        $productBottom = (int) ($settings['card_product_bottom_offset'] ?? 100);

        $storedPaths = [];
        $predictionIds = [];
        $quantity = max(1, min(10, (int) ($settings['quantity'] ?? 1)));

        $usePuppeteer = $this->cardUsePuppeteer();

        Log::info('ProcessPhotoGuidedGenerationJob: card_scene_regen_overlay', [
            'generation_job_id' => $job->id,
            'lines_count' => count($lines),
            'scene_model' => (string) ($modelConfig['id'] ?? ''),
            'aspect_ratio' => $aspectRatio,
            'output_px' => "{$w}x{$h}",
            'vision_overlay' => filter_var(config('platform.card.php_composite.vision_overlay_analysis', true), FILTER_VALIDATE_BOOLEAN),
            'puppeteer' => $usePuppeteer,
        ]);

        for ($index = 0; $index < $quantity; $index++) {
            [$modelId, $input] = $this->buildKontextCardInput($dataUri, $regenPrompt, $modelConfig, $aspectRatio);
            $pred = $replicate->createPrediction($modelId, $input);
            $predictionIds[] = $pred['id'];
            $sceneUrl = $this->pollUntilDone($replicate, $pred['id'], self::MAX_POLLS);

            $overlayAnalysis = null;
            if (filter_var(config('platform.card.php_composite.vision_overlay_analysis', true), FILTER_VALIDATE_BOOLEAN)) {
                $sceneResp = Http::timeout(120)->get($sceneUrl);
                if ($sceneResp->ok()) {
                    $overlayAnalysis = $vision->analyzeRawBytes((string) $sceneResp->body(), 'jpg', 'card_overlay_analysis_system');
                } else {
                    Log::warning('ProcessPhotoGuidedGenerationJob: scene download for vision failed', [
                        'generation_job_id' => $job->id,
                        'status' => $sceneResp->status(),
                    ]);
                }
            }

            $accent = $this->resolveAccentFromOverlayVision($overlayAnalysis, $baseAccent);

            if ($usePuppeteer) {
                /** @var CardPuppeteerRendererService $puppeteer */
                $puppeteer = app(CardPuppeteerRendererService::class);
                $scenePath = $this->downloadUrlToTempFile($sceneUrl, '.jpg');
                try {
                    $payload = $this->buildPuppeteerRenderPayload($job, $settings, $scenePath, $w, $h, $lines, $accent);
                    $outPath = $puppeteer->renderCard($payload);
                } finally {
                    @unlink($scenePath);
                }
            } else {
                $productRect = $this->productRectFromOverlayVision($overlayAnalysis, $w, $h)
                    ?? $this->fallbackProductRectForCard($w, $h);

                $texts = CardInfographicTextLayout::forLines($lines, $w, $h, $accent, $fontBold, $fontReg, $productRect);

                $outPath = $composer->compose(
                    $sceneUrl,
                    null,
                    $texts,
                    [
                        'diagonal_lines' => filter_var(
                            config('platform.card.php_composite.diagonal_accents', false),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'accent_color' => $accent,
                        'user_accent' => $accent,
                        'auto_text_contrast' => filter_var(
                            config('platform.card.php_composite.auto_text_contrast', true),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'local_text_contrast' => filter_var(
                            config('platform.card.php_composite.local_text_contrast', true),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'infographic_callouts' => filter_var(
                            config('platform.card.php_composite.infographic_callouts', false),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'text_halo_on_light_patch' => filter_var(
                            config('platform.card.php_composite.text_halo_on_light_patch', true),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'product_path_for_layout' => null,
                        'product_scale' => $productScale,
                        'product_bottom_offset' => $productBottom,
                        'product_shadow' => false,
                        'width' => $w,
                        'height' => $h,
                    ],
                );
            }

            $relPath = $this->storeCardRasterFile($job, $index, $outPath);
            $storedPaths[] = $relPath;
            if (is_file($outPath) && $outPath !== $relPath) {
                @unlink($outPath);
            }
        }

        if ($storedPaths === []) {
            throw new RuntimeException('Card scene regen did not produce any file.');
        }

        $this->finalizeCardPhpCompositeJob(
            $job,
            $settings,
            $storedPaths,
            $predictionIds,
            $usePuppeteer ? 'puppeteer_scene_regen' : 'php_scene_regen_overlay'
        );
    }

    /**
     * Legacy: T2I empty backdrop + optional reference pasted as product layer + text.
     */
    private function processCardLegacyT2IAndProductPaste(GenerationJob $job): void
    {
        /** @var ReplicateService $replicate */
        $replicate = app(ReplicateService::class);
        $composer = CardComposerService::fromConfig();
        $builder = app(ProductPromptBuilder::class);
        $settings = $job->settings_json ?? [];
        $disk = ReelForgeStorage::contentDisk();
        $firstImage = $job->project->images()->orderBy('order')->first();
        if ($firstImage === null) {
            throw new RuntimeException('No reference image for product card.');
        }
        if (! Storage::disk($disk)->exists($firstImage->path)) {
            throw new RuntimeException('Reference image is missing in storage.');
        }

        $tmpProduct = @tempnam(sys_get_temp_dir(), 'rf_pcard_');
        if ($tmpProduct === false) {
            throw new RuntimeException('Could not create a temp file for the product image.');
        }
        file_put_contents($tmpProduct, Storage::disk($disk)->get($firstImage->path));

        $storedPaths = [];
        $predictionIds = [];
        $quantity = max(1, min(10, (int) ($settings['quantity'] ?? 1)));
        $longEdge = (int) config('platform.card.php_composite.long_edge', 1080);
        [$w, $h] = CardOutputDimensions::pixelsForAspect((string) ($settings['aspect_ratio'] ?? '1:1'), $longEdge);
        $modelKey = (string) config('platform.card.php_composite.replicate_model_key', 'preview');
        $modelConfig = config("prompts.models.{$modelKey}", config('prompts.models.default'));
        $modelId = (string) $modelConfig['id'];
        $enriched = (string) ($settings['_enriched_wishes'] ?? '');
        try {
            $refLuma = ImageBrightness::averageLumaFromFile($tmpProduct);
        } catch (\Throwable) {
            $refLuma = 128.0;
        }
        $productIsLight = $refLuma >= 128.0;
        $bgPrompt = $builder->buildCardBackgroundPromptForComposite(
            $enriched,
            (string) ($job->image_caption ?? ''),
            $productIsLight
        );
        $lines = $this->cardWishLines($settings);
        if ($lines === []) {
            $lines = $this->defaultCardCopyLines($job);
            Log::info('ProcessPhotoGuidedGenerationJob: card user_wishes empty, overlay uses product name/title', [
                'generation_job_id' => $job->id,
            ]);
        }
        $fontBold = (string) config('platform.card.php_composite.font_bold', 'Montserrat-Bold.ttf');
        $fontReg = (string) config('platform.card.php_composite.font_regular', 'Montserrat-Regular.ttf');
        $accent = (string) ($settings['card_accent_color'] ?? config('platform.card.php_composite.default_accent', '#d4af37'));
        $productScale = (float) ($settings['card_product_scale'] ?? 0.58);
        $productBottom = (int) ($settings['card_product_bottom_offset'] ?? 100);
        $productRect = CardProductPlacement::rectFromImagePath($tmpProduct, $w, $h, $productScale, $productBottom);
        $usePuppeteer = $this->cardUsePuppeteer();
        $texts = $usePuppeteer
            ? []
            : CardInfographicTextLayout::forLines($lines, $w, $h, $accent, $fontBold, $fontReg, $productRect);
        $productPathForCompose = filter_var(
            config('platform.card.php_composite.composite_product_layer', true),
            FILTER_VALIDATE_BOOLEAN
        ) ? $tmpProduct : null;
        Log::info('ProcessPhotoGuidedGenerationJob: card_php_composite', [
            'generation_job_id' => $job->id,
            'lines_count' => count($lines),
            'text_blocks' => count($texts),
            'font_bold' => $fontBold,
            'bg_model' => $modelId,
            'aspect_ratio' => (string) ($settings['aspect_ratio'] ?? '1:1'),
            'output_px' => "{$w}x{$h}",
            'ref_luma' => round($refLuma, 1),
            'product_is_light' => $productIsLight,
            'puppeteer' => $usePuppeteer,
        ]);

        try {
            for ($index = 0; $index < $quantity; $index++) {
                $input = $this->buildReplicateT2IInput($modelId, $modelConfig, $bgPrompt, $w, $h);
                $pred = $replicate->createPrediction($modelId, $input);
                $predictionIds[] = $pred['id'];
                $resultUrl = $this->pollUntilDone($replicate, $pred['id'], self::MAX_POLLS);

                $outPath = $composer->compose(
                    $resultUrl,
                    $productPathForCompose,
                    $texts,
                    [
                        'diagonal_lines' => filter_var(
                            config('platform.card.php_composite.diagonal_accents', false),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'accent_color' => $accent,
                        'user_accent' => $accent,
                        'auto_text_contrast' => filter_var(
                            config('platform.card.php_composite.auto_text_contrast', true),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'local_text_contrast' => filter_var(
                            config('platform.card.php_composite.local_text_contrast', true),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'infographic_callouts' => filter_var(
                            config('platform.card.php_composite.infographic_callouts', false),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'text_halo_on_light_patch' => filter_var(
                            config('platform.card.php_composite.text_halo_on_light_patch', true),
                            FILTER_VALIDATE_BOOLEAN
                        ),
                        'product_path_for_layout' => $tmpProduct,
                        'product_scale' => $productScale,
                        'product_bottom_offset' => $productBottom,
                        'product_shadow' => true,
                        'width' => $w,
                        'height' => $h,
                    ],
                );

                if ($usePuppeteer) {
                    /** @var CardPuppeteerRendererService $puppeteer */
                    $puppeteer = app(CardPuppeteerRendererService::class);
                    $payload = $this->buildPuppeteerRenderPayload($job, $settings, $outPath, $w, $h, $lines, $accent);
                    $newPath = $puppeteer->renderCard($payload);
                    if (is_file($outPath)) {
                        @unlink($outPath);
                    }
                    $outPath = $newPath;
                }

                $relPath = $this->storeCardRasterFile($job, $index, $outPath);
                $storedPaths[] = $relPath;
                if (is_file($outPath) && $outPath !== $relPath) {
                    @unlink($outPath);
                }
            }
        } finally {
            if (is_file($tmpProduct)) {
                @unlink($tmpProduct);
            }
        }

        if ($storedPaths === []) {
            throw new RuntimeException('Card PHP composite did not produce any file.');
        }

        $this->finalizeCardPhpCompositeJob(
            $job,
            $settings,
            $storedPaths,
            $predictionIds,
            $usePuppeteer ? 'puppeteer_legacy_t2i' : 'php_imagick_gd'
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<string>  $storedPaths
     * @param  list<string>  $predictionIds
     */
    private function finalizeCardPhpCompositeJob(
        GenerationJob $job,
        array $settings,
        array $storedPaths,
        array $predictionIds,
        string $cardComposition
    ): void {
        $primary = $storedPaths[0];
        $provider = str_starts_with($cardComposition, 'puppeteer') ? 'replicate+puppeteer' : 'replicate+php';
        $job->update([
            'status' => 'done',
            'provider' => $provider,
            'result_path' => $primary,
            'settings_json' => array_merge($settings, [
                'result_paths' => $storedPaths,
                'replicate_prediction_ids' => $predictionIds,
                'replicate_prediction_id' => $predictionIds[0] ?? null,
                'card_composition' => $cardComposition,
            ]),
        ]);
        $job->project()->update([
            'status' => 'done',
            'video_path' => $primary,
        ]);

        Log::info('ProcessPhotoGuidedGenerationJob: card PHP composite done', [
            'generation_job_id' => $job->id,
            'result_paths' => $storedPaths,
            'card_composition' => $cardComposition,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $overlay
     */
    private function resolveAccentFromOverlayVision(?array $overlay, string $fallbackAccent): string
    {
        if (! filter_var(config('platform.card.php_composite.vision_typography_from_scene', true), FILTER_VALIDATE_BOOLEAN)) {
            return $fallbackAccent;
        }
        if (! is_array($overlay)) {
            return $fallbackAccent;
        }
        $hex = $overlay['suggested_accent_hex'] ?? null;
        if (is_string($hex) && preg_match('/^#[0-9A-Fa-f]{6}$/', trim($hex)) === 1) {
            return trim($hex);
        }

        return $fallbackAccent;
    }

    /**
     * @param  array<string, mixed>|null  $overlay
     * @return array{x: int, y: int, w: int, h: int}|null
     */
    private function productRectFromOverlayVision(?array $overlay, int $w, int $h): ?array
    {
        if (! is_array($overlay)) {
            return null;
        }
        $box = $overlay['product_bounding_box'] ?? null;
        if (! is_array($box)) {
            return null;
        }
        $xMin = (float) ($box['x_min'] ?? 0);
        $yMin = (float) ($box['y_min'] ?? 0);
        $xMax = (float) ($box['x_max'] ?? 0);
        $yMax = (float) ($box['y_max'] ?? 0);
        if ($xMax <= $xMin || $yMax <= $yMin) {
            return null;
        }

        return [
            'x' => (int) max(0, round($xMin * $w)),
            'y' => (int) max(0, round($yMin * $h)),
            'w' => (int) max(32, round(($xMax - $xMin) * $w)),
            'h' => (int) max(32, round(($yMax - $yMin) * $h)),
        ];
    }

    /**
     * @return array{x: int, y: int, w: int, h: int}
     */
    private function fallbackProductRectForCard(int $w, int $h): array
    {
        return [
            'x' => (int) ($w * 0.12),
            'y' => (int) ($h * 0.28),
            'w' => (int) ($w * 0.76),
            'h' => (int) ($h * 0.62),
        ];
    }

    /**
     * @param  array<string, mixed>  $modelConfig
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildKontextCardInput(string $dataUri, string $prompt, array $modelConfig, string $aspectRatio): array
    {
        $input = [
            'prompt' => $prompt,
            'input_image' => $dataUri,
            'aspect_ratio' => $aspectRatio,
            'output_format' => (string) ($modelConfig['output_format'] ?? 'jpg'),
            'safety_tolerance' => (int) ($modelConfig['safety_tolerance'] ?? 2),
            // Off: upsampling often drops explicit "no watermark" constraints.
            'prompt_upsampling' => false,
        ];
        if (isset($modelConfig['output_quality'])) {
            $input['output_quality'] = (int) $modelConfig['output_quality'];
        }

        return [(string) $modelConfig['id'], $input];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return list<string>
     */
    private function cardWishLines(array $settings): array
    {
        $raw = (string) ($settings['user_wishes'] ?? '');
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
        if ($out === [] && trim($raw) !== '') {
            $out[] = trim($raw);
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function defaultCardCopyLines(GenerationJob $job): array
    {
        $p = $job->project;
        if ($p === null) {
            return ['PRODUCT'];
        }
        $name = trim((string) $p->title);
        $meta = is_array($p->product_meta_json) ? $p->product_meta_json : null;
        if (is_array($meta) && isset($meta['name']) && is_string($meta['name']) && trim($meta['name']) !== '') {
            $name = trim($meta['name']);
        }
        if ($name === '') {
            $name = (string) config('app.name', 'Product');
        }

        return [mb_strtoupper($name, 'UTF-8')];
    }

    /**
     * @param  array<string, mixed>  $modelConfig
     */
    private function buildReplicateT2IInput(
        string $modelId,
        array $modelConfig,
        string $prompt,
        int $w,
        int $h
    ): array {
        if (stripos($modelId, 'schnell') !== false) {
            return [
                'prompt' => $prompt,
                'num_inference_steps' => (int) ($modelConfig['num_inference_steps'] ?? 4),
                'width' => $w,
                'height' => $h,
            ];
        }

        return [
            'prompt' => $prompt,
            'negative_prompt' => (string) config('prompts.negative', ''),
            'width' => $w,
            'height' => $h,
            'num_inference_steps' => (int) ($modelConfig['num_inference_steps'] ?? 28),
            'guidance_scale' => (float) ($modelConfig['guidance_scale'] ?? 3.5),
        ];
    }

    private function storeCardRasterFile(GenerationJob $job, int $index, string $localPath): string
    {
        $body = @file_get_contents($localPath);
        if ($body === false || $body === '') {
            throw new RuntimeException('Failed to read composed card file from disk.');
        }
        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (! in_array($ext, ['jpg', 'png'], true)) {
            $ext = 'jpg';
        }
        $disk = ReelForgeStorage::contentDisk();
        $path = ReelForgeStorage::userContentPrefix()
            ."/{$job->user_id}/projects/{$job->project_id}/generated_{$job->id}_{$index}.{$ext}";
        Storage::disk($disk)->put($path, $body);

        return $path;
    }

    private function cardUsePuppeteer(): bool
    {
        return filter_var(config('platform.card.puppeteer.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<string>  $lines
     * @return array<string, mixed>
     */
    private function buildPuppeteerRenderPayload(
        GenerationJob $job,
        array $settings,
        string $imageAbsPath,
        int $w,
        int $h,
        array $lines,
        string $accent,
    ): array {
        $copy = $this->puppeteerCardCopy($job, $lines, $settings);
        $template = (string) ($settings['card_html_template'] ?? 'fullbg');
        $fmt = strtolower((string) ($settings['card_output_format'] ?? 'jpg'));
        if ($fmt === 'jpeg') {
            $fmt = 'jpg';
        }
        $outputType = $fmt === 'png' ? 'png' : 'jpeg';

        $tmpBase = @tempnam(sys_get_temp_dir(), 'rf_pup_');
        if ($tmpBase === false) {
            throw new RuntimeException('Could not create temp path for Puppeteer output.');
        }
        @unlink($tmpBase);
        $outPath = $tmpBase.($outputType === 'png' ? '.png' : '.jpg');

        $payload = [
            'template' => $template,
            'imagePath' => $imageAbsPath,
            'width' => $w,
            'height' => $h,
            'badges' => $copy['badges'],
            'title' => $copy['title'],
            'description' => $copy['description'],
            'price' => $copy['price'],
            'priceOld' => $copy['priceOld'],
            'accent' => $accent,
            'outputType' => $outputType,
            'jpegQuality' => (int) config('platform.card.puppeteer.jpeg_quality', 92),
            'deviceScaleFactor' => (int) config('platform.card.puppeteer.device_scale_factor', 2),
            'outPath' => $outPath,
        ];

        $exe = (string) config('platform.card.puppeteer.executable_path', '');
        if ($exe !== '') {
            $payload['executablePath'] = $exe;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  list<string>  $lines
     * @return array{badges: list<string>, title: string, description: string, price: string, priceOld: string}
     */
    private function puppeteerCardCopy(GenerationJob $job, array $lines, array $settings): array
    {
        $project = $job->project;
        $title = trim((string) ($project?->title ?? ''));
        $meta = is_array($project?->product_meta_json) ? $project->product_meta_json : [];
        if (isset($meta['name']) && is_string($meta['name']) && trim($meta['name']) !== '') {
            $title = trim($meta['name']);
        }
        if ($title === '') {
            $title = (string) config('app.name', 'Product');
        }

        $badges = array_values(array_slice($lines, 0, 4));
        $rest = array_slice($lines, 4);
        $description = $rest !== [] ? implode("\n", $rest) : trim((string) ($project?->description ?? ''));

        return [
            'title' => $title,
            'badges' => $badges,
            'description' => $description,
            'price' => $this->formatProjectPrice($project),
            'priceOld' => trim((string) ($settings['card_price_old'] ?? '')),
        ];
    }

    private function formatProjectPrice(?Project $project): string
    {
        if ($project === null || $project->price === null) {
            return '';
        }
        $n = (float) $project->price;
        $decimals = abs($n - round($n)) < 0.001 ? 0 : 2;

        return number_format($n, $decimals, ',', ' ').' ₽';
    }

    private function downloadUrlToTempFile(string $url, string $suffix = '.jpg'): string
    {
        $response = Http::timeout(120)->get($url);
        if (! $response->ok()) {
            throw new RuntimeException('Failed to download scene image (HTTP '.$response->status().').');
        }
        $tmpBase = @tempnam(sys_get_temp_dir(), 'rf_scene_');
        if ($tmpBase === false) {
            throw new RuntimeException('Could not create temp file for scene download.');
        }
        @unlink($tmpBase);
        $path = $tmpBase.$suffix;
        if (@file_put_contents($path, $response->body()) === false) {
            throw new RuntimeException('Could not write scene temp file.');
        }

        return $path;
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
            $modelConfig = config('prompts.models.kontext');
            $settings = $job->settings_json ?? [];
            $contentTypeI2I = (string) ($settings['content_type'] ?? 'photo');
            $allowed = ['9:16', '3:4', '1:1', '4:3', '16:9'];
            $aspectRatio = $modelConfig['aspect_ratio'];
            if (isset($settings['aspect_ratio']) && in_array($settings['aspect_ratio'], $allowed, true)) {
                $aspectRatio = $settings['aspect_ratio'];
            }
            // Card: upsampling rewrites the prompt and often garbles on-image Cyrillic — keep the exact user prompt.
            $upsampling = $contentTypeI2I === 'card' ? false : (bool) ($modelConfig['prompt_upsampling'] ?? true);

            $input = [
                'prompt' => $prompt,
                'input_image' => $imageBase64,
                'aspect_ratio' => $aspectRatio,
                'output_format' => $modelConfig['output_format'],
                'safety_tolerance' => $modelConfig['safety_tolerance'],
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
        $modelKey = $contentType === 'preview' ? 'preview' : 'default';
        $modelConfig = config("prompts.models.{$modelKey}", config('prompts.models.default'));

        return [
            $modelConfig['id'],
            [
                'prompt' => $prompt,
                'negative_prompt' => config('prompts.negative'),
                'width' => $modelConfig['width'],
                'height' => $modelConfig['height'],
                'num_inference_steps' => $modelConfig['num_inference_steps'],
                'guidance_scale' => $modelConfig['guidance_scale'],
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
            'image' => $imageUri,
            'num_frames' => $frames,
            'num_inference_steps' => max(1, (int) ($cfg['num_inference_steps'] ?? 25)),
        ];

        $width = (int) ($cfg['width'] ?? 0);
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

        $bytes = Storage::disk($disk)->get($firstImage->path);
        $extension = strtolower(pathinfo($firstImage->path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    private function pollUntilDone(ReplicateService $replicate, string $predictionId, int $maxPolls): string
    {
        for ($i = 0; $i < $maxPolls; $i++) {
            sleep(self::POLL_INTERVAL_SEC);

            $prediction = $replicate->getPrediction($predictionId);

            Log::debug('ProcessPhotoGuidedGenerationJob: poll', [
                'attempt' => $i + 1,
                'status' => $prediction['status'],
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
                throw new RuntimeException('Replicate prediction failed: '.($prediction['error'] ?? 'unknown error'));
            }
        }

        throw new RuntimeException('Replicate polling timeout after '.($maxPolls * self::POLL_INTERVAL_SEC).' seconds.');
    }

    private function downloadAndStore(string $mediaUrl, GenerationJob $job, int $index = 0): string
    {
        $response = Http::timeout(180)->get($mediaUrl);

        if ($response->failed()) {
            throw new RuntimeException('Failed to download generated media from: '.$mediaUrl);
        }

        $disk = ReelForgeStorage::contentDisk();
        $extension = $this->guessMediaExtension($response, $mediaUrl);
        $path = ReelForgeStorage::userContentPrefix()
            ."/{$job->user_id}/projects/{$job->project_id}/generated_{$job->id}_{$index}.{$extension}";

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

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
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
            'error' => $exception->getMessage(),
        ]);

        app(CreditService::class)->refundFailedPhotoGuidedGeneration($fresh);

        $fresh->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        $fresh->project()->update(['status' => 'failed']);
    }
}
