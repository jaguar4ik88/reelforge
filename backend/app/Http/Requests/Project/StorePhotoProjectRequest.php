<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePhotoProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image'        => ['required_without:images', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'images'       => ['required_without:image', 'array', 'min:1', 'max:4'],
            'images.*'     => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            /** @deprecated use product_name */
            'title'        => ['nullable', 'string', 'max:200'],
            'product_name' => ['required', 'string', 'max:200'],
            'category'     => ['required', 'string', 'in:apparel,electronics,home,beauty,food,sports,other'],
            /** Catalog template (not the internal photo-guided placeholder). */
            'template_id'  => [
                'nullable',
                'integer',
                Rule::exists('templates', 'id')->where(
                    fn ($q) => $q->where('is_active', true)->where('slug', '!=', 'photo-guided-internal')
                ),
            ],
        ];
    }
}
