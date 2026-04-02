<?php

namespace App\Http\Controllers\API;

use App\DTO\CreateProjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\CreateProjectRequest;
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
            ->with(['template', 'images'])
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => ProjectResource::collection($projects)->response()->getData(true),
        ]);
    }

    public function store(CreateProjectRequest $request): JsonResponse
    {
        $dto     = CreateProjectDTO::fromArray($request->validated(), $request->user()->id);
        $project = $this->projectService->create($dto);

        return response()->json([
            'success' => true,
            'message' => __('messages.project.created'),
            'data'    => new ProjectResource($project->load(['template', 'images'])),
        ], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project, $request);

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => new ProjectResource($project->load(['template', 'images'])),
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
