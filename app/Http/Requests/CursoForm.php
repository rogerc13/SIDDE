<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Curso;

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
            /*
            case 'POST':
                return [                        
                        'titulo' => 'required|string|max:'.Curso::MAX_LENGTH_TITULO,
                        'categoria_id' => 'required|integer|exists:categoria,id',
                        'modalidad' => 'required|string|max:'.Curso::MAX_LENGTH_MODALIDAD,
                        'duracion' => 'required|numeric',
                        'dirigido' => 'required|string|max:'.Curso::MAX_LENGTH_DIRIGIDO,
                        'contenido' => 'required|string|max:'.Curso::MAX_LENGTH_CONTENIDO,
                        'objetivo' => 'required|string|max:'.Curso::MAX_LENGTH_OBJETIVO,                        
                        'min' => 'required|integer|min:1|max:'.$this->max,
                        'max' => 'required|integer|min:'.$this->min,
                        
                       ]; 
                       
            case 'PUT':
                return [
                        'titulo' => 'required|string|max:'.Curso::MAX_LENGTH_TITULO,
                        'categoria_id' => 'required|integer|exists:categoria,id',
                        'modalidad' => 'required|string|max:'.Curso::MAX_LENGTH_MODALIDAD,
                        'duracion' => 'required|numeric',
                        'dirigido' => 'required|string|max:'.Curso::MAX_LENGTH_DIRIGIDO,
                        'contenido' => 'required|string|max:'.Curso::MAX_LENGTH_CONTENIDO,
                        'objetivo' => 'required|string|max:'.Curso::MAX_LENGTH_OBJETIVO,                        
                        'min' => 'required|integer|min:1|max:'.$this->max,
                        'max' => 'required|integer|min:'.$this->min,
                       ];
            */
            default:return[];
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
