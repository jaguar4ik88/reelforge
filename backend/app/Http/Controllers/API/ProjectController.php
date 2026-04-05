<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\Project\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()
            ->projects()
            ->with(['template', 'images', 'latestGenerationJob'])
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => ProjectResource::collection($projects)->response()->getData(true),
        ]);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project, $request);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => new ProjectResource($project->load(['template', 'images', 'generationJobs'])),
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project, $request);

        $validated = $request->validate([
            'final_prompt' => 'nullable|string|max:65535',
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => new ProjectResource($project->fresh()->load(['template', 'images', 'generationJobs'])),
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project, $request);
        $this->projectService->delete($project);

        return response()->json([
            'success' => true,
            'message' => __('messages.project.deleted'),
            'data'    => [],
        ]);
    }

    private function authorizeProject(Project $project, Request $request): void
    {
        abort_if(! $project->isOwnedBy($request->user()), 403, 'Forbidden.');
    }
}
