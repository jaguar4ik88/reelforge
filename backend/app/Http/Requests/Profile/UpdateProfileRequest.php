<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'string', 'min:2', 'max:100'],
            'email'  => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'locale' => ['sometimes', 'string', Rule::in(['uk', 'en'])],
            'avatar' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
