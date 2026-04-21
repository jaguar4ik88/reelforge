<?php

namespace App\Http\Controllers\API;

use App\Exceptions\InsufficientCreditsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StorePhotoGenerationRequest;
use App\Http\Requests\Project\StorePhotoProjectRequest;
use App\Http\Resources\GenerationJobResource;
use App\Http\Resources\ProjectResource;
use App\Jobs\ProcessPhotoGuidedGenerationJob;
use App\Models\GenerationJob;
use App\Models\Project;
use App\Services\Credits\CreditService;
use App\Services\Subscriptions\SubscriptionEntitlementService;
use App\Services\Product\ProductPromptBuilder;
use App\Services\Product\WishesPromptEnrichmentService;
use App\Services\Project\PhotoGuidedProjectService;
use App\Services\Vision\ProductImageCaptionService;
use App\Services\Vision\ProductPhotoAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhotoGuidedProjectController extends Controller
{
    public function __construct(
        private readonly PhotoGuidedProjectService $photoGuidedProjectService,
        private readonly ProductImageCaptionService $captionService,
        private readonly ProductPhotoAnalysisService $productPhotoAnalysisService,
        private readonly ProductPromptBuilder $promptBuilder,
        private readonly WishesPromptEnrichmentService $wishesEnrichment,
        private readonly CreditService $creditService,
        private readonly SubscriptionEntitlementService $subscriptionEntitlements,
    ) {}

    public function store(StorePhotoProjectRequest $request): JsonResponse
    {
        $files = $request->hasFile('images')
            ? array_values($request->file('images'))
            : [$request->file('image')];

        $productName = $request->validated('product_name');
        $category    = $request->validated('category');
        $templateId  = $request->validated('template_id');

        if ($templateId !== null && $this->subscriptionEntitlements->activeSubscriptionPlan($request->user()) === null) {
            return response()->json([
                'success' => false,
                'message' => __('messages.photo_guided.template_requires_subscription'),
                'errors'  => [],
            ], 422);
        }

        $project = $this->photoGuidedProjectService->createFromProductPhotos(
            $request->user(),
            $files,
            $productName,
            $category,
            $templateId !== null ? (int) $templateId : null,
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.photo_guided.project_created'),
            'data'    => new ProjectResource($project->load(['template', 'images'])),
        ], 201);
    }

    /**
     * Vision analysis of the first uploaded product photo — name, category, qualities.
     */
    public function analyzeProduct(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project, $request);

        abort_if(! $project->isPhotoGuided(), 422, __('messages.photo_guided.not_photo_project'));
        abort_if($project->images()->count() < 1, 422, __('messages.photo_guided.need_reference_image'));

        $meta = $this->productPhotoAnalysisService->analyze($project->load('images'));

        $project->update([
            'title'             => $meta['name'],
            'product_meta_json' => $meta,
        ]);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => [
                'product' => $meta,
                'project' => new ProjectResource($project->fresh()->load(['template', 'images'])),
            ],
        ]);
    }

    public function startGeneration(StorePhotoGenerationRequest $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project, $request);

        abort_if(! $project->isPhotoGuided(), 422, __('messages.photo_guided.not_photo_project'));
        abort_if($project->status !== 'draft', 422, __('messages.photo_guided.project_locked'));
        abort_if($project->images()->count() < 1, 422, __('messages.photo_guided.need_reference_image'));

        if ($project->template_id !== null && $this->subscriptionEntitlements->activeSubscriptionPlan($request->user()) === null) {
            return response()->json([
                'success' => false,
                'message' => __('messages.photo_guided.template_requires_subscription'),
                'errors'  => [],
            ], 422);
        }

        if ($project->generationJobs()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.photo_guided.already_running'),
                'errors'  => [],
            ], 422);
        }

        $validated = $request->validated();

        if (($validated['content_type'] ?? '') === 'video') {
            $code = $this->subscriptionEntitlements->photoGuidedVideoRestrictionCode($request->user());
            if ($code !== null) {
                $min = (int) config('reelforge.credits.photo_guided_video.min_balance', 10);
                $message = match ($code) {
                    'low_credits' => __('messages.photo_guided.video_blocked_low_credits', ['min' => $min]),
                    'no_subscription' => __('messages.photo_guided.video_blocked_no_subscription'),
                    'starter_plan' => __('messages.photo_guided.video_blocked_starter_plan'),
                    default => __('messages.photo_guided.video_blocked_no_subscription'),
                };

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors'  => ['content_type' => [$message]],
                ], 422);
            }
        }

        $maxBatch = $this->subscriptionEntitlements->maxBatchQuantityPerGeneration($request->user());
        $quantity = max(1, min($maxBatch, (int) ($validated['quantity'] ?? 1)));
        $validated['quantity'] = $quantity;

        $videoSeconds = ($validated['content_type'] ?? '') === 'video'
            ? (int) ($validated['video_duration_seconds'] ?? 5)
            : null;
        $unitCost = $this->creditService->getPhotoGuidedGenerationCost(
            $validated['content_type'],
            $videoSeconds,
            ($validated['content_type'] ?? '') === 'photo' ? ($validated['scene_style'] ?? 'from_wishes') : null
        );
        $cost = $unitCost * $quantity;
        if (config('reelforge.credits.require_for_generation', true)) {
            if ($cost < 1) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.photo_guided.generation_requires_credits'),
                    'errors'  => [],
                ], 422);
            }
            if (! $this->creditService->canSpend($request->user(), $cost)) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.photo_guided.insufficient_credits'),
                    'errors'  => [],
                ], 422);
            }
        }

        $this->mergeProductOverridesFromRequest($project, $validated);
        $project->refresh();

        $firstImage = $project->images()->orderBy('order')->first();
        $caption     = $this->captionService->describe($firstImage);
        $rawWishes   = $validated['user_wishes'] ?? '';
        $enriched    = $this->wishesEnrichment->enrich(
            $project->title,
            $validated['product_category'] ?? 'other',
            $validated['content_type'],
            $validated['scene_style'],
            $rawWishes,
        );
        $prompt = $this->promptBuilder->build(
            $validated['content_type'],
            $validated['scene_style'],
            $enriched,
            $rawWishes,
            $caption,
            $videoSeconds,
        );

        $project->loadMissing('template');
        $tpl = $project->template;
        if ($tpl !== null && filled($tpl->generation_prompt)) {
            $styleBlock = trim((string) $tpl->generation_prompt);
            $prompt = "Catalog reference style (match this look and mood; keep the user's product as the subject):\n\n"
                .$styleBlock
                ."\n\n---\n\n"
                .$prompt;
        }

        try {
            $job = DB::transaction(function () use ($request, $project, $validated, $caption, $prompt, $cost) {
                $project->forceFill(['final_prompt' => $prompt])->save();

                $job = GenerationJob::query()->create([
                    'user_id'       => $request->user()->id,
                    'project_id'    => $project->id,
                    'kind'          => 'photo_guided',
                    'status'        => 'pending',
                    'settings_json' => $validated,
                    'image_caption' => $caption !== '' ? $caption : null,
                    'final_prompt'  => $prompt,
                    'provider'      => 'stub',
                ]);

                if (config('reelforge.credits.require_for_generation', true) && $cost > 0) {
                    $tx = $this->creditService->spendForPhotoGuidedGeneration($request->user(), $job, $cost);
                    $job->forceFill([
                        'credits_cost'             => $cost,
                        'credits_transaction_id'   => $tx->id,
                    ])->save();
                }

                return $job->fresh();
            });
        } catch (InsufficientCreditsException) {
            return response()->json([
                'success' => false,
                'message' => __('messages.photo_guided.insufficient_credits'),
                'errors'  => [],
            ], 422);
        }

        ProcessPhotoGuidedGenerationJob::dispatch($job)->afterCommit();

        return response()->json([
            'success' => true,
            'message' => __('messages.photo_guided.started'),
            'data'    => [
                'project'         => new ProjectResource($project->fresh()->load(['template', 'images'])),
                'generation_job'  => new GenerationJobResource($job),
            ],
        ], 201);
    }

    private function authorizeProject(Project $project, Request $request): void
    {
        abort_if(! $project->isOwnedBy($request->user()), 403, 'Forbidden.');
    }

    /**
     * User may edit name / category / qualities in step 2 before generating.
     *
     * @param  array<string, mixed>  $validated
     */
    private function mergeProductOverridesFromRequest(Project $project, array $validated): void
    {
        $name = isset($validated['product_name']) ? trim((string) $validated['product_name']) : '';
        $cat  = isset($validated['product_category']) ? trim((string) $validated['product_category']) : '';
        $qual = $validated['product_qualities'] ?? null;

        if ($name === '' && $cat === '' && ! is_array($qual)) {
            return;
        }

        $meta = $project->product_meta_json ?? [];

        if ($name !== '') {
            $meta['name'] = mb_substr($name, 0, 200);
        }
        if ($cat !== '') {
            $meta['category'] = $cat;
        }
        if (is_array($qual)) {
            $meta['qualities'] = array_values(array_filter(array_map(
                static fn ($q) => mb_substr(trim((string) $q), 0, 200),
                $qual
            )));
        }

        $project->update([
            'title'             => $meta['name'] ?? $project->title,
            'product_meta_json' => $meta,
        ]);
    }
}
