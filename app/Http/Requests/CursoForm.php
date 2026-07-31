<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
                        'codigo' => 'required|string|max:300',
                        'titulo' => 'required|string|max:'.Course::MAX_LENGTH_TITLE,
                        'categoria_id' => 'required|integer|exists:categories,id',
                        'modalidad_id' => 'required|integer|exists:modalities,id',
                        'duracion' => 'required|numeric|min:1',
                        'dirigido' => 'required|string|max:'.Course::MAX_LENGTH_ADDRESSED,
                        'objetivo' => 'required|string|max:'.Course::MAX_LENGTH_OBJECTIVE,
                        'min' => 'required|integer|min:1',
                        'max' => 'required|integer|min:1',
                       ];

            case 'PUT':
                return [
                        'codigo' => 'required|string|max:300',
                        'titulo' => 'required|string|max:'.Course::MAX_LENGTH_TITLE,
                        'categoria_id' => 'required|integer|exists:categories,id',
                        'modalidad_id' => 'required|integer|exists:modalities,id',
                        'duracion' => 'required|numeric|min:1',
                        'dirigido' => 'required|string|max:'.Course::MAX_LENGTH_ADDRESSED,
                        'objetivo' => 'required|string|max:'.Course::MAX_LENGTH_OBJECTIVE,
                        'min' => 'required|integer|min:1',
                        'max' => 'required|integer|min:1',
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
            'min.min' => 'El campo :attribute debe ser mayor a  0.',
            'min.max' => 'El campo :attribute debe ser menor a :max .',
            'max.min' => 'El campo :attribute debe ser mayor a  :min.',           
        ];
    }
}
