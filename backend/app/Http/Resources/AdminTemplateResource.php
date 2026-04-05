<?php

namespace App\Http\Resources;

use App\Support\ReelForgeStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'slug'               => $this->slug,
            'category'           => $this->category,
            'is_active'          => $this->is_active,
            'sort_order'         => $this->sort_order,
            'preview_url'        => ReelForgeStorage::url(
                ReelForgeStorage::templatesDisk(),
                $this->preview_path,
                2
            ),
            'generation_prompt'  => $this->generation_prompt,
            'negative_prompt'    => $this->negative_prompt,
            'config'             => $this->config_json,
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
