<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use App\Services\Image\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PhotoGuidedProjectService
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function createFromProductPhoto(User $user, UploadedFile $file, ?string $title = null): Project
    {
        $templateId = $this->internalTemplateId();

        $project = Project::query()->create([
            'user_id'       => $user->id,
            'creation_flow' => 'photo_guided',
            'title'         => $title !== null && $title !== '' ? Str::limit($title, 200, '') : __('messages.photo_guided.default_title'),
            'price'         => 0,
            'description'   => __('messages.photo_guided.default_description'),
            'template_id'   => $templateId,
            'status'        => 'draft',
        ]);

        $this->imageService->uploadOne($project, $file, 1);

        return $project->fresh(['images', 'template']);
    }

    private function internalTemplateId(): int
    {
        $id = Template::query()->where('slug', 'photo-guided-internal')->value('id');
        if ($id === null) {
            throw new \RuntimeException('Missing template slug "photo-guided-internal". Run: php artisan db:seed --class=TemplateSeeder');
        }

        return (int) $id;
    }
}
