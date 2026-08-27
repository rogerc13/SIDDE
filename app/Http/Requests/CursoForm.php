<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Course;

class CursoForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        switch($this->method())
        {
            case 'POST':
                return [
                        'codigo' => [
                            'required',
                            'string',
                            'max:255',
                            'regex:/^[A-Za-z0-9_\-]+$/',
                            Rule::unique('courses', 'code'),
                        ],
                        'titulo' => 'required|string|max:'.Course::MAX_LENGTH_TITLE,
                        'categoria_id' => 'required|integer|exists:categories,id',
                        'modalidad_id' => 'required|integer|exists:modalities,id',
                        'duracion' => 'required|numeric|min:1',
                        'dirigido' => 'required|string|max:'.Course::MAX_LENGTH_ADDRESSED,
                        'objetivo' => 'required|string|max:'.Course::MAX_LENGTH_OBJECTIVE,
                        'min' => 'required|integer|min:1',
                        'max' => 'required|integer|min:1',
                        'prerequisite' => [
                            'nullable',
                            'string',
                            'max:255',
                            'regex:/^[A-Za-z0-9_\-]+$/',
                            Rule::exists('courses', 'code'),
                        ],
                        'manual_f' => 'nullable|file|mimes:pdf,pptx,xlsx,docx|max:10240',
                        'manual_p' => 'nullable|file|mimes:pdf,pptx,xlsx,docx|max:10240',
                        'guia' => 'nullable|file|mimes:pdf,pptx,xlsx,docx|max:10240',
                        'presentacion' => 'nullable|file|mimes:pdf,pptx,xlsx,docx|max:10240',
                        'content_data' => 'nullable|string|max:10000',
                       ];

            case 'PUT':
                return [
                        'codigo' => [
                            'required',
                            'string',
                            'max:255',
                            'regex:/^[A-Za-z0-9_\-]+$/',
                            Rule::unique('courses', 'code')->ignore($this->route('id')),
                        ],
                        'titulo' => 'required|string|max:'.Course::MAX_LENGTH_TITLE,
                        'categoria_id' => 'required|integer|exists:categories,id',
                        'modalidad_id' => 'required|integer|exists:modalities,id',
                        'duracion' => 'required|numeric|min:1',
                        'dirigido' => 'required|string|max:'.Course::MAX_LENGTH_ADDRESSED,
                        'objetivo' => 'required|string|max:'.Course::MAX_LENGTH_OBJECTIVE,
                        'min' => 'required|integer|min:1',
                        'max' => 'required|integer|min:1',
                        'prerequisite' => [
                            'nullable',
                            'string',
                            'max:255',
                            'regex:/^[A-Za-z0-9_\-]+$/',
                            Rule::exists('courses', 'code'),
                        ],
                        'manual_f' => 'nullable|file|mimes:pdf,pptx,xlsx,docx|max:10240',
                        'manual_p' => 'nullable|file|mimes:pdf,pptx,xlsx,docx|max:10240',
                        'guia' => 'nullable|file|mimes:pdf,pptx,xlsx,docx|max:10240',
                        'presentacion' => 'nullable|file|mimes:pdf,pptx,xlsx,docx|max:10240',
                        'content_data' => 'nullable|string|max:10000',
                       ];

            default:
                return [];
        }
    }
    
    public function messages()
    {
        return [
            'string' => 'El campo :attribute debe ser una cadena caracteres.', 
            'required' => 'El campo :attribute es necesario.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'min.min' => 'El campo :attribute debe ser mayor a 0.',
            'min.integer' => 'El campo :attribute debe ser un número entero.',
            'max.max' => 'El campo :attribute debe ser menor a :max.',
            'codigo.regex' => 'El código solo puede contener letras, números, guión y guión bajo.',
            'codigo.unique' => 'Ya existe una acción de formación con ese código.',
            'prerequisite.exists' => 'El código de prerequisito no corresponde a una acción de formación existente.',
            'prerequisite.regex' => 'El código de prerequisito solo puede contener letras, números, guión y guión bajo.',
        ];
    }
}
