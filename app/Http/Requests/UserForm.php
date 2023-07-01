<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class UserForm extends FormRequest
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
                        'nombre' => 'required|string|max:'.User::MAX_LENGTH_NAME,
                        'apellido' => 'required|string|max:'.User::MAX_LENGTH_LAST_NAME, 
                        'ci' => 'required|string|max:'.User::MAX_LENGTH_ID_NUMBER,
                        'rol' => 'sometimes|required|integer|exists:roles,id',                        
                        'email' => 'required|email:rfc,filter|unique:users,email|max:'.User::MAX_LENGTH_EMAIL,
                        'password' => 'required|confirmed',
                        'password_confirmation' => 'required',
                       

                       ]; 
            case 'PUT':
                return [
                        'nombre' => 'required|string|max:'.User::MAX_LENGTH_NAME,
                        'apellido' => 'required|string|max:'.User::MAX_LENGTH_LAST_NAME, 
                        'ci' => 'required|string|max:'.User::MAX_LENGTH_ID_NUMBER,
                        'rol' => 'sometimes|required|integer|exists:roles,id',                        
                        'email' => 'required|email:rfc,filter|unique:users,email,'.$this->user_id.'|max:'.User::MAX_LENGTH_EMAIL,
                        'password' => 'sometimes|confirmed',
                        'password_confirmation' => 'required_with:password',
                       ];
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
            'email' => 'El campo :attribute debe ser un email.',
            'max' => 'El campo :attribute debe contener maximo :max caracteres.',  
            'exists' => 'El rol seleccionado no existe en nuestra base de datos.',
            'unique'  => 'El campo :attribute ya se encuentra en nuestra base de datos',   
            'password.required' => 'El campo contraseña es obligatorio.',
            'password.confirmed' => 'El campo contraseña no coincide.',
            'password_confirmation.required' => 'El campo Confirmar contraseña es necesario.',   
        ];
    }
}