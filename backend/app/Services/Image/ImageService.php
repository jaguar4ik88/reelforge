<?php

namespace App\Services\Image;

use App\Models\Project;
use App\Support\ReelForgeStorage;
use App\Models\ProjectImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function __construct(
        private readonly ImageResizeService $imageResize,
    ) {}

    public function uploadMany(Project $project, array $files): Collection
    {
        foreach ($project->images as $image) {
            $this->delete($image);
        }

        $uploaded = collect();
        foreach ($files as $order => $file) {
            $uploaded->push($this->uploadOne($project, $file, $order + 1));
        }

        return $uploaded;
    }

    public function uploadOne(Project $project, UploadedFile $file, int $order): ProjectImage
    {
        $disk     = ReelForgeStorage::contentDisk();
        $toStore  = $this->imageResize->resizeUploadedFileIfNeeded($file);
        $path     = $toStore->store(ReelForgeStorage::projectImagesPath($project), $disk);

        return $project->images()->create([
            'path'  => $path,
            'order' => $order,
        ]);
    }

    public function delete(ProjectImage $image): void
    {
        Storage::disk(ReelForgeStorage::contentDisk())->delete($image->path);
        $image->delete();
    }
}
