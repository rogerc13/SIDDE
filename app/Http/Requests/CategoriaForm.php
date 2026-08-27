<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Category;

class CategoriaForm extends FormRequest
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
                        'nombre'=>'required|string|max:'.Category::MAX_LENGTH_NAME.'|unique:categories,name',
                       ]; 
                
            case 'PUT':
                return [
                        'nombre'=>'required|string|max:'.Category::MAX_LENGTH_NAME.'|unique:categories,name,'.$this->route('id'),
                       ];
            default:return[];
        }
    }

        public function messages()
    {
        return [
             
            'required' => 'El campo :attribute es necesario.',
            'string' => 'El campo :attribute debe ser una cadena caracteres.',
            'max' => 'El campo :attribute debe contener maximo :max caracteres.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',

            
        ];
    }
}
