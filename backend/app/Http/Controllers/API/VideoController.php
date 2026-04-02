<?php

namespace App\Http\Controllers\API;

use App\Exceptions\InsufficientCreditsException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Jobs\GenerateVideoJob;
use App\Models\Project;
use App\Services\Credits\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoController extends Controller
{
    public function generate(Request $request, Project $project): JsonResponse
    {
        abort_if(! $project->isOwnedBy($request->user()), 403, __('messages.project.forbidden'));
        abort_if($project->isPhotoGuided(), 422, __('messages.video.photo_guided_use_photo_flow'));
        abort_if($project->status === 'processing', 422, __('messages.video.already_processing'));
        abort_if($project->images()->count() < 3, 422, __('messages.video.need_images'));
        abort_if(! $request->user()->canGenerateVideo(), 422, $this->denyGenerateMessage());

        $creditService = app(CreditService::class);
        $cost          = $creditService->getOperationCost('video_generation');

        try {
            DB::transaction(function () use ($request, $project, $creditService, $cost) {
                $locked = Project::query()->lockForUpdate()->findOrFail($project->id);
                abort_if($locked->status === 'processing', 422, __('messages.video.already_processing'));

                if (config('reelforge.credits.require_for_generation', true)) {
                    $tx = $creditService->spendForVideoGeneration($request->user(), $locked, $cost);
                    $locked->update([
                        'status'                 => 'processing',
                        'credits_cost'           => $cost,
                        'credits_transaction_id' => $tx->id,
                    ]);
                } else {
                    $locked->update(['status' => 'processing']);
                }
            });
        } catch (InsufficientCreditsException $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.video.insufficient_credits'),
                'errors'  => [],
            ], 422);
        }

        $project->refresh();

        GenerateVideoJob::dispatch($project);

        return response()->json([
            'success' => true,
            'message' => __('messages.video.started'),
            'data'    => new ProjectResource($project->load(['template', 'images'])),
        ]);
    }

    private function denyGenerateMessage(): string
    {
        if (config('reelforge.credits.require_for_generation', true)) {
            return __('messages.video.insufficient_credits');
        }

        return __('messages.video.limit_reached');
    }
}
