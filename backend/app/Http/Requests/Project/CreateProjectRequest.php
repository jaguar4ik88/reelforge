<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:2', 'max:200'],
            'price'       => ['required', 'numeric', 'min:0', 'max:9999999'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'template_id' => ['required', 'integer', 'exists:templates,id'],
        ];
    }
}
