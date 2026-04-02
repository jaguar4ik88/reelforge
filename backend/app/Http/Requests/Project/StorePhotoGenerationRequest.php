<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotoGenerationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content_type' => ['required', 'string', 'in:photo,card,video'],
            'scene_style'  => ['required', 'string', 'in:in_use,environment,studio'],
            'user_wishes'  => ['nullable', 'string', 'max:2000'],
        ];
    }
}
