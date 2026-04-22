<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\Project\ProjectService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 12);
        $perPage = min(max($perPage, 1), 100);

        $query = $request->user()
            ->projects()
            ->with(['template', 'images', 'latestGenerationJob'])
            ->latest();

        if ($request->filled('project_id')) {
            $query->where('id', (int) $request->query('project_id'));
        }

        $this->applyGalleryFilterKind($query, (string) $request->query('filter_kind', 'all'));

        $projects = $query->paginate($perPage)->withQueryString();

        $payload = ProjectResource::collection($projects)->response()->getData(true);
        if (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) {
            $payload['items'] = $payload['data'];
        }

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => $payload,
        ]);
    }

    /**
     * Lightweight id+title list for gallery filters (not paginated; capped).
     */
    public function compactIndex(Request $request): JsonResponse
    {
        $rows = $request->user()
            ->projects()
            ->select(['id', 'title'])
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data'    => $rows,
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

    private function applyGalleryFilterKind(Relation $query, string $filterKind): void
    {
        if (! in_array($filterKind, ['video', 'draft', 'processing'], true)) {
            return;
        }

        if ($filterKind === 'draft') {
            $query->where('status', 'draft');

            return;
        }

        if ($filterKind === 'processing') {
            $query->where('status', 'processing');

            return;
        }

        // "video" — same idea as gallery UI: done + video output (template) or photo_guided I2V
        $query->where('status', 'done')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('video_path')
                        ->where('video_path', '!=', '')
                        ->where(function ($q3) {
                            $q3->where('creation_flow', '!=', 'photo_guided')
                                ->orWhereNull('creation_flow');
                        });
                })->orWhere(function ($q2) {
                    $q2->where('creation_flow', 'photo_guided')
                        ->whereHas('latestGenerationJob', function ($j) {
                            $j->where('settings_json->content_type', 'video');
                        })
                        ->whereNotNull('video_path')
                        ->where('video_path', '!=', '');
                });
            });
    }

    private function authorizeProject(Project $project, Request $request): void
    {
        abort_if(! $project->isOwnedBy($request->user()), 403, 'Forbidden.');
    }
}
