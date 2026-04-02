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
use App\Services\Product\ProductPromptBuilder;
use App\Services\Project\PhotoGuidedProjectService;
use App\Services\Vision\ProductImageCaptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhotoGuidedProjectController extends Controller
{
    public function __construct(
        private readonly PhotoGuidedProjectService $photoGuidedProjectService,
        private readonly ProductImageCaptionService $captionService,
        private readonly ProductPromptBuilder $promptBuilder,
        private readonly CreditService $creditService,
    ) {}

    public function store(StorePhotoProjectRequest $request): JsonResponse
    {
        $project = $this->photoGuidedProjectService->createFromProductPhoto(
            $request->user(),
            $request->file('image'),
            $request->input('title'),
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.photo_guided.project_created'),
            'data'    => new ProjectResource($project->load(['template', 'images'])),
        ], 201);
    }

    public function startGeneration(StorePhotoGenerationRequest $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project, $request);

        abort_if(! $project->isPhotoGuided(), 422, __('messages.photo_guided.not_photo_project'));
        abort_if($project->status !== 'draft', 422, __('messages.photo_guided.project_locked'));
        abort_if($project->images()->count() < 1, 422, __('messages.photo_guided.need_reference_image'));

        if ($project->generationJobs()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.photo_guided.already_running'),
                'errors'  => [],
            ], 422);
        }

        $cost = $this->creditService->getOperationCost('photo_guided_generation');
        if (config('reelforge.credits.require_for_generation', true) && $cost > 0) {
            if (! $this->creditService->canSpend($request->user(), $cost)) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.photo_guided.insufficient_credits'),
                    'errors'  => [],
                ], 422);
            }
        }

        $validated = $request->validated();
        $firstImage = $project->images()->orderBy('order')->first();
        $caption     = $this->captionService->describe($firstImage);
        $prompt      = $this->promptBuilder->build(
            $validated['content_type'],
            $validated['scene_style'],
            $validated['user_wishes'] ?? '',
            $caption,
        );

        try {
            $job = DB::transaction(function () use ($request, $project, $validated, $caption, $prompt, $cost) {
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
}
