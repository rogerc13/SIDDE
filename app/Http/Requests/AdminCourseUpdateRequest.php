<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminCourseUpdateRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:300'],
            'objective' => ['required', 'string', 'max:3000'],
            'duration' => ['required', 'integer', 'min:0'],
            'addressed' => ['required', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'modality_id' => ['required', 'integer', 'exists:modalities,id'],
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
            'category_id.exists' => 'El área seleccionada no es válida.',
            'modality_id.exists' => 'La modalidad seleccionada no es válida.',
        ];
    }
}
