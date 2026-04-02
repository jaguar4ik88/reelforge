<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenerationJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'kind'          => $this->kind,
            'status'        => $this->status,
            'settings'      => $this->settings_json,
            'image_caption' => $this->image_caption,
            'final_prompt'  => $this->final_prompt,
            'provider'      => $this->provider,
            'credits_cost'  => $this->credits_cost,
            'error_message' => $this->error_message,
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
