<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotoProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'title' => ['nullable', 'string', 'max:200'],
        ];
    }
}
