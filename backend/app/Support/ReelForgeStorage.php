<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class ReelForgeStorage
{
    public static function contentDisk(): string
    {
        return (string) config('reelforge.storage.content_disk');
    }

    public static function templatesDisk(): string
    {
        return (string) config('reelforge.storage.templates_disk');
    }

    public static function userContentPrefix(): string
    {
        return trim((string) config('reelforge.storage.user_content_prefix', 'users'), '/');
    }

    public static function templatesPathPrefix(): string
    {
        return trim((string) config('reelforge.storage.templates_path_prefix', 'templates'), '/');
    }

    public static function avatarsPath(int $userId): string
    {
        return self::userContentPrefix() . "/{$userId}/avatars";
    }

    public static function projectImagesPath(Project $project): string
    {
        return self::userContentPrefix() . "/{$project->user_id}/projects/{$project->id}/images";
    }

    public static function projectVideoRelativePath(int $userId, int $projectId): string
    {
        return self::userContentPrefix() . "/{$userId}/projects/{$projectId}/video.mp4";
    }

    public static function url(string $disk, ?string $path, int $expiresHours = 2): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver === 's3') {
            return Storage::disk($disk)->temporaryUrl($path, now()->addHours($expiresHours));
        }

        return Storage::disk($disk)->url($path);
    }
}
