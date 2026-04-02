<?php

namespace App\Services\Project;

use App\DTO\CreateProjectDTO;
use App\Models\Project;
use App\Services\Image\ImageService;
use App\Support\ReelForgeStorage;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    public function create(CreateProjectDTO $dto): Project
    {
        return Project::create([
            'user_id'     => $dto->userId,
            'title'       => $dto->title,
            'price'       => $dto->price,
            'description' => $dto->description,
            'template_id' => $dto->templateId,
            'status'      => 'draft',
        ]);
    }

    public function delete(Project $project): void
    {
        $disk = ReelForgeStorage::contentDisk();

        foreach ($project->images as $image) {
            Storage::disk($disk)->delete($image->path);
        }

        if ($project->video_path) {
            Storage::disk($disk)->delete($project->video_path);
        }

        $project->delete();
    }
}
