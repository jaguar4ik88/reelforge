<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Image\UploadImagesRequest;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Services\Image\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function __construct(private readonly ImageService $imageService) {}

    public function upload(UploadImagesRequest $request, Project $project): JsonResponse
    {
        abort_if(! $project->isOwnedBy($request->user()), 403, 'Forbidden.');
        abort_if($project->status !== 'draft', 422, 'Cannot upload images to a project that is already processing or done.');

        $images = $this->imageService->uploadMany($project, $request->file('images'));

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully.',
            'data'    => $images->map(fn ($img) => [
                'id'    => $img->id,
                'url'   => $img->url,
                'order' => $img->order,
            ]),
        ], 201);
    }

    public function destroy(Request $request, Project $project, ProjectImage $image): JsonResponse
    {
        abort_if(! $project->isOwnedBy($request->user()), 403, 'Forbidden.');
        abort_if($image->project_id !== $project->id, 404, 'Image not found.');

        $this->imageService->delete($image);

        return response()->json([
            'success' => true,
            'message' => 'Image deleted.',
            'data'    => [],
        ]);
    }
}
