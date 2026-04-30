<?php

namespace App\Http\Requests\Infographic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInfographicByExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:12288'],
            'example_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:12288'],
            'example_filename' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:200'],
            'characteristics' => ['nullable', 'string', 'max:8000'],
            'aspect_ratio' => ['required', 'string', 'in:1:1,4:5,9:16,16:9'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $hasFile = $this->hasFile('example_image');
            $name = trim((string) $this->input('example_filename', ''));
            if (! $hasFile && $name === '') {
                $v->errors()->add('example_image', __('messages.infographic_by_example.need_example'));
            }
            if ($hasFile && $name !== '') {
                $v->errors()->add('example_image', __('messages.infographic_by_example.example_one_source'));
            }
        });
    }
}
