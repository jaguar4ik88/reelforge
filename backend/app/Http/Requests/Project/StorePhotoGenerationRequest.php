<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePhotoGenerationRequest extends FormRequest
{
    public function rules(): array
    {
        $contentType = $this->input('content_type');
        $sceneAllowed  = $contentType === 'photo'
            ? ['from_wishes', 'in_use', 'studio']
            : ['in_use', 'environment', 'studio'];

        return [
            'content_type'           => ['required', 'string', 'in:photo,card,video'],
            'scene_style'            => ['required', 'string', Rule::in($sceneAllowed)],
            'user_wishes'            => ['nullable', 'string', 'max:2000'],
            'video_duration_seconds' => [
                'nullable',
                'integer',
                Rule::in([5, 20]),
                Rule::requiredIf(fn () => $this->input('content_type') === 'video'),
            ],
            'product_name'           => ['nullable', 'string', 'max:200'],
            'product_category'     => ['nullable', 'string', 'in:apparel,electronics,home,beauty,food,sports,other'],
            'product_qualities'    => ['nullable', 'array', 'max:6'],
            'product_qualities.*'  => ['string', 'max:200'],
            'quantity'               => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
