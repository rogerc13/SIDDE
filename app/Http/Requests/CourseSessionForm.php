<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CourseSession;
use App\Models\Scheduled;
use Carbon\Carbon;

class CourseSessionForm extends FormRequest
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
        $scheduledId = $this->route('id');
        
        switch($this->method())
        {
            case 'POST':
                return [
                    'ubicacion' => 'required|integer|exists:locations,id',
                    'fecha_sesion' => 'required|date|date_format:Y-m-d',
                    'hora_inicio' => 'required|date_format:H:i',
                    'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                    'notas' => 'nullable|string|max:500',
                ];
            
            case 'PUT':
                return [
                    'ubicacion' => 'required|integer|exists:locations,id',
                    'fecha_sesion' => 'required|date|date_format:Y-m-d',
                    'hora_inicio' => 'required|date_format:H:i',
                    'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                    'notas' => 'nullable|string|max:500',
                ];
            
            default:
                return [];
        }
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es necesario.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'exists' => 'El campo :attribute no existe en nuestra base de datos.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'date_format' => 'El campo :attribute debe tener el formato :format.',
            'after' => 'El campo :attribute debe ser posterior a :after.',
            'string' => 'El campo :attribute debe ser una cadena de caracteres.',
            'max' => 'El campo :attribute debe contener máximo :max caracteres.',
        ];
    }

    /**
     * Handle post-validation validation.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $scheduledId = $this->route('id');
            $scheduled = Scheduled::find($scheduledId);

            if (!$scheduled) {
                $validator->errors()->add('scheduled', 'El curso programado no existe.');
                return;
            }

            // Validate session date falls within scheduled course dates
            $sessionDate = Carbon::parse($this->fecha_sesion);
            $startDate = Carbon::parse($scheduled->start_date);
            $endDate = Carbon::parse($scheduled->end_date);

            if ($sessionDate->lt($startDate) || $sessionDate->gt($endDate)) {
                $validator->errors()->add('fecha_sesion', 'La fecha de la sesión debe estar dentro del rango del curso programado (' . $scheduled->start_date . ' al ' . $scheduled->end_date . ').');
            }

            // Validate duration does not exceed course duration
            $newDuration = Carbon::parse($this->hora_inicio)->diffInMinutes(Carbon::parse($this->hora_fin)) / 60;
            $totalExisting = $scheduled->totalSessionHours();
            $maxDuration = $scheduled->course->duration;

            if (($totalExisting + $newDuration) > $maxDuration) {
                $remaining = $maxDuration - $totalExisting;
                $validator->errors()->add('hora_fin', 'La duración excede el total del curso. Disponible: ' . $remaining . ' horas.');
            }

            // Check for time overlap (excluding current session on update)
            $query = CourseSession::where('location_id', $this->ubicacion)
                ->where('session_date', $this->fecha_sesion)
                ->where('start_time', '<', $this->hora_fin)
                ->where('end_time', '>', $this->hora_inicio)
                ->whereNull('deleted_at');

            // Exclude current session on update
            if ($this->isMethod('PUT')) {
                $sessionId = $this->route('sessionId');
                $query->where('id', '!=', $sessionId);
            }

            if ($query->count() > 0) {
                $validator->errors()->add('ubicacion', 'Esta ubicación ya tiene una sesión programada en este horario.');
            }
        });
    }
}
