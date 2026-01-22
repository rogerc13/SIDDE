<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminCourseCodeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $courseId = $this->route('id');

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9_\-]+$/',
                Rule::unique('courses', 'code')->ignore($courseId),
            ],
            'restore' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El campo código es necesario.',
            'code.regex' => 'El código solo puede contener letras, números, guión y guión bajo.',
            'code.unique' => 'Ya existe una acción de formación con ese código.',
        ];
    }
}
