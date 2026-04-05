<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Support\ReelForgeStorage;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
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
