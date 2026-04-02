<?php

namespace App\Http\Requests\Image;

use Illuminate\Foundation\Http\FormRequest;

class UploadImagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'images'             => ['required', 'array', 'min:3', 'max:5'],
            'images.*'           => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.min'   => 'Upload at least 3 images.',
            'images.max'   => 'Maximum 5 images allowed.',
            'images.*.mimes' => 'Only JPG, PNG, and WebP images are allowed.',
            'images.*.max'   => 'Each image must be under 10MB.',
        ];
    }
}
