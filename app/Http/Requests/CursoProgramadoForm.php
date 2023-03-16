<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CursoProgramado;

class CursoProgramadoForm extends FormRequest
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
                        'facilitador' => 'required|integer|exists:users,id',
                        'titulo' => 'required|integer|exists:curso,id',
                        'fecha_i' => 'required|date|after_or_equal: today',
                        'fecha_f' => 'required|date|after:fecha_i',

                       ];
            case 'PUT':
                return [
                        'facilitador' => 'required|integer|exists:users,id',
                        'fecha_i' => 'required|date|after_or_equal: today',
                        'fecha_f' => 'required|date|after:fecha_i',
                       ];
            default:return[];
        }
    }

    public function messages()
    {
        return [

            'required' => 'El campo :attribute es necesario.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'fecha_i.after_or_equal' => 'La fecha inicio no puede ser inferior a la fecha de hoy.',
            'fecha_f.after' => 'La fecha culminación no puede ser inferior a la fecha de inicio.',
            'exists' => 'El campo :attribute no existe en nuestra base de datos.',

        ];
    }
}
