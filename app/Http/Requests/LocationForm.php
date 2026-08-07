<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Location;

class LocationForm extends FormRequest
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
        switch ($this->method()) {

            case 'POST':
                return [
                    'nombre' => 'required|string|max:' . Location::MAX_LENGTH_NAME . '|unique:locations,name',
                    'floor_id' => 'required|integer|exists:floors,id',
                ];

            case 'PUT':
                return [
                    'nombre' => 'required|string|max:' . Location::MAX_LENGTH_NAME,
                    'floor_id' => 'required|integer|exists:floors,id',
                ];
            default:
                return [];
        }
    }

        public function messages()
    {
        return [
             
            'required' => 'El campo :attribute es necesario.',
            'string' => 'El campo :attribute debe ser una cadena caracteres.',
            'max' => 'El campo :attribute debe contener maximo :max caracteres.',
            'unique' => 'El campo :attribute ya existe.',

            
        ];
    }
}
