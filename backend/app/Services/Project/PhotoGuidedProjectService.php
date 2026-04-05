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

    /**
     * @param  list<UploadedFile>  $files  1–4 images
     */
    public function createFromProductPhotos(User $user, array $files, string $productName, string $category, ?int $catalogTemplateId = null): Project
    {
        $templateId = $catalogTemplateId !== null
            ? (int) $catalogTemplateId
            : $this->internalTemplateId();

        $name = Str::limit(trim($productName), 200, '');
        if ($name === '') {
            $name = __('messages.photo_guided.default_title');
        }

        $meta = [
            'name'      => $name,
            'category'  => $category,
            'qualities' => [],
        ];

        $project = Project::query()->create([
            'user_id'             => $user->id,
            'creation_flow'       => 'photo_guided',
            'title'               => $name,
            'price'               => 0,
            'description'         => __('messages.photo_guided.default_description'),
            'product_meta_json'   => $meta,
            'template_id'         => $templateId,
            'status'              => 'draft',
        ]);

        $this->imageService->uploadMany($project, $files);

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
