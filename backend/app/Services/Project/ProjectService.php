<?php

namespace App\Services\Project;

use App\Models\GenerationJob;
use App\Models\Project;
use App\Support\ReelForgeStorage;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    /**
     * Remove stored assets then the whole project folder on disk (`users/{id}/projects/{projectId}`), then DB row.
     * Generation jobs cascade on project delete.
     */
    public function delete(Project $project): void
    {
        $disk = ReelForgeStorage::contentDisk();

        $project->loadMissing(['images', 'generationJobs']);

        $treeRoot = ReelForgeStorage::projectRootPath($project);

        $seen = [];

        foreach ($project->generationJobs as $job) {
            foreach ($this->storagePathsFromGenerationJob($job) as $path) {
                if ($path === '' || isset($seen[$path])) {
                    continue;
                }
                $seen[$path] = true;
                if (! $this->pathInsideProjectRoot($path, $treeRoot)) {
                    Storage::disk($disk)->delete($path);
                }
            }
        }

        foreach ($project->images as $image) {
            $path = $image->path ?? '';
            if ($path === '' || isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;
            if (! $this->pathInsideProjectRoot($path, $treeRoot)) {
                Storage::disk($disk)->delete($path);
            }
        }

        if (filled($project->video_path) && ! isset($seen[$project->video_path])) {
            $vp = $project->video_path;
            if (! $this->pathInsideProjectRoot($vp, $treeRoot)) {
                Storage::disk($disk)->delete($vp);
            }
        }

        $normalizedRoot = rtrim(str_replace('\\', '/', $treeRoot), '/');
        if ($normalizedRoot !== '') {
            Storage::disk($disk)->deleteDirectory($normalizedRoot);
        }

        $project->delete();
    }

    /** @return list<string> */
    private function storagePathsFromGenerationJob(GenerationJob $job): array
    {
        $out = [];

        if (filled($job->result_path)) {
            $out[] = $job->result_path;
        }

        $settings = $job->settings_json ?? [];
        foreach ((array) ($settings['result_paths'] ?? []) as $p) {
            if (is_string($p) && trim($p) !== '') {
                $out[] = trim($p);
            }
        }

        return $out;
    }

    /**
     * True if relative path stays under `{prefix}/{userId}/projects/{projectId}`.
     */
    private function pathInsideProjectRoot(string $path, string $root): bool
    {
        $path = strtolower(str_replace('\\', '/', trim($path, '/')));
        $rootNorm = strtolower(str_replace('\\', '/', trim($root, '/')));
        if ($rootNorm === '' || $path === '') {
            return false;
        }

        return $path === $rootNorm || str_starts_with($path, $rootNorm.'/');
    }
}
