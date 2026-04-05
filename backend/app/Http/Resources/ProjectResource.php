<?php

namespace App\Http\Resources;

use App\Support\ReelForgeStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'creation_flow'  => $this->creation_flow ?? 'template',
            'title'          => $this->title,
            'price'          => $this->price,
            'description'    => $this->description,
            'status'         => $this->status,
            'template'       => $this->when(
                ($this->creation_flow ?? 'template') !== 'photo_guided' && $this->relationLoaded('template') && $this->template !== null,
                fn () => [
                    'id'   => $this->template->id,
                    'name' => $this->template->name,
                ]
            ),
            'images'      => $this->whenLoaded('images', fn () =>
                $this->images->map(fn ($img) => [
                    'id'    => $img->id,
                    'url'   => ReelForgeStorage::url(ReelForgeStorage::contentDisk(), $img->path, 2),
                    'order' => $img->order,
                ])
            ),
            'video_url'   => $this->when(
                $this->status === 'done' && $this->video_path && ($this->creation_flow ?? 'template') !== 'photo_guided',
                fn () => ReelForgeStorage::url(ReelForgeStorage::contentDisk(), $this->video_path, 24)
            ),
            'result_url'  => $this->when(
                $this->status === 'done' && $this->video_path && ($this->creation_flow ?? 'template') === 'photo_guided',
                fn () => ReelForgeStorage::url(ReelForgeStorage::contentDisk(), $this->video_path, 24)
            ),
            'product_meta' => $this->when(
                ($this->creation_flow ?? 'template') === 'photo_guided',
                fn () => $this->product_meta_json
            ),
            'final_prompt' => $this->final_prompt,
            'generation'  => $this->whenLoaded('generationJobs', function () {
                $latest = $this->generationJobs->sortByDesc('created_at')->first();
                if (! $latest) {
                    return null;
                }
                return [
                    'final_prompt'  => $latest->final_prompt,
                    'image_caption' => $latest->image_caption,
                    'status'        => $latest->status,
                    'settings'      => $latest->settings_json,
                ];
            }),
            'generation_preview' => $this->when(
                $this->relationLoaded('latestGenerationJob'),
                function () {
                    $j = $this->latestGenerationJob;
                    if (! $j) {
                        return null;
                    }

                    return [
                        'content_type' => $j->settings_json['content_type'] ?? 'photo',
                    ];
                }
            ),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
