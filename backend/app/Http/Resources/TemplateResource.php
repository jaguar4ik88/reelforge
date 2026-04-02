<?php

namespace App\Http\Resources;

use App\Support\ReelForgeStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class TemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'category'    => $this->category,
            'sort_order'  => $this->sort_order,
            'preview_url' => ReelForgeStorage::url(
                ReelForgeStorage::templatesDisk(),
                $this->preview_path,
                2
            ),
            'config'      => $this->config_json,
        ];
    }
}
