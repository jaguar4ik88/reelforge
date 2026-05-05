<?php

namespace App\Http\Controllers\API;

use App\Exceptions\InsufficientCreditsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Infographic\StoreInfographicByExampleRequest;
use App\Http\Resources\GenerationJobResource;
use App\Http\Resources\ProjectResource;
use App\Jobs\ProcessInfographicByExampleJob;
use App\Models\GenerationJob;
use App\Services\Credits\CreditService;
use App\Services\Infographic\InfographicCanvasTemplateService;
use App\Services\Infographic\InfographicCardExampleService;
use App\Services\Project\PhotoGuidedProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InfographicByExampleController extends Controller
{
    public function __construct(
        private readonly InfographicCardExampleService $cardExamples,
        private readonly InfographicCanvasTemplateService $canvasTemplates,
        private readonly PhotoGuidedProjectService $photoGuidedProjectService,
        private readonly CreditService $creditService,
    ) {}

    public function cardExamples(Request $request): JsonResponse
    {
        $items = $this->cardExamples->listForApi();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => ['items' => $items],
        ]);
    }

    public function canvasTemplates(Request $request): JsonResponse
    {
        $items = $this->canvasTemplates->listForApi();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => ['items' => $items],
        ]);
    }

    public function store(StoreInfographicByExampleRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $exampleServicePath = null;
        $exampleSource = null;
        $exampleUploadPath = null;

        if ($request->hasFile('example_image')) {
            $exampleSource = 'upload';
            $exampleUploadPath = $request->file('example_image')->store(
                'infographic_example_uploads/'.$user->id,
                'local'
            );
        } else {
            $fn = trim((string) ($validated['example_filename'] ?? ''));
            $exampleServicePath = $this->cardExamples->resolvePublicExamplePath($fn);
            if ($exampleServicePath === null) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.infographic_by_example.invalid_example_file'),
                    'errors' => ['example_filename' => [__('messages.infographic_by_example.invalid_example_file')]],
                ], 422);
            }
            $exampleSource = 'gallery';
        }

        $aspectUi = (string) $validated['aspect_ratio'];
        $kontextAspect = match ($aspectUi) {
            '1:1' => '1:1',
            '4:5' => '3:4',
            '9:16' => '9:16',
            '16:9' => '16:9',
            default => '1:1',
        };

        $cost = $this->creditService->getInfographicCardByExampleCost();
        if (config('platform.credits.require_for_generation', true)) {
            if ($cost < 1) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.photo_guided.generation_requires_credits'),
                    'errors' => [],
                ], 422);
            }
            if (! $this->creditService->canSpend($user, $cost)) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.photo_guided.insufficient_credits'),
                    'errors' => [],
                ], 422);
            }
        }

        $project = $this->photoGuidedProjectService->createFromProductPhotos(
            $user,
            [$request->file('product_image')],
            (string) $validated['title'],
            'other',
        );

        $desc = trim((string) ($validated['characteristics'] ?? ''));
        if ($desc !== '') {
            $project->update(['description' => mb_substr($desc, 0, 8000)]);
        }

        if ($project->generationJobs()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.photo_guided.already_running'),
                'errors' => [],
            ], 422);
        }

        $settings = [
            'content_type' => 'photo',
            'pipeline' => 'infographic_by_example',
            'quantity' => 1,
            'aspect_ratio' => $kontextAspect,
            'aspect_ratio_ui' => $aspectUi,
            'infographic_title' => (string) $validated['title'],
            'infographic_characteristics' => (string) ($validated['characteristics'] ?? ''),
            'example_source' => $exampleSource,
            'example_public_path' => $exampleSource === 'gallery' ? $exampleServicePath : null,
            'example_upload_path' => $exampleSource === 'upload' ? $exampleUploadPath : null,
            'product_name' => (string) $validated['title'],
            'product_category' => 'other',
            'scene_style' => 'studio',
        ];

        try {
            $job = DB::transaction(function () use ($request, $project, $settings, $cost) {
                $project->update(['title' => (string) $settings['infographic_title']]);

                $job = GenerationJob::query()->create([
                    'user_id' => $request->user()->id,
                    'project_id' => $project->id,
                    'kind' => 'photo_guided',
                    'status' => 'pending',
                    'settings_json' => $settings,
                    'image_caption' => null,
                    'final_prompt' => '',
                    'provider' => 'stub',
                ]);

                if (config('platform.credits.require_for_generation', true) && $cost > 0) {
                    $tx = $this->creditService->spendForPhotoGuidedGeneration($request->user(), $job, $cost);
                    $job->forceFill([
                        'credits_cost' => $cost,
                        'credits_transaction_id' => $tx->id,
                    ])->save();
                }

                return $job->fresh();
            });
        } catch (InsufficientCreditsException) {
            if ($exampleSource === 'upload' && $exampleUploadPath !== null && Storage::disk('local')->exists($exampleUploadPath)) {
                Storage::disk('local')->delete($exampleUploadPath);
            }
            $project->delete();

            return response()->json([
                'success' => false,
                'message' => __('messages.photo_guided.insufficient_credits'),
                'errors' => [],
            ], 422);
        }

        ProcessInfographicByExampleJob::dispatch($job)->afterCommit();

        return response()->json([
            'success' => true,
            'message' => __('messages.infographic_by_example.started'),
            'data' => [
                'project' => new ProjectResource($project->fresh()->load(['images', 'template'])),
                'generation_job' => new GenerationJobResource($job),
            ],
        ], 201);
    }
}
