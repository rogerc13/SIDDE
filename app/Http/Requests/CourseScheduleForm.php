<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Scheduled;
use Carbon\Carbon;

class CourseScheduleForm extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'titulo' => 'required|integer|exists:courses,id',
            'facilitador' => 'required|integer|exists:facilitators,id',
            'sessions' => 'required|array|min:1',
            'sessions.*.location_id' => 'required|integer|exists:locations,id',
            'sessions.*.session_date' => 'required|date|date_format:Y-m-d',
            'sessions.*.start_time' => 'required|date_format:H:i',
            'sessions.*.end_time' => 'required|date_format:H:i|after:sessions.*.start_time',
            'sessions.*.notes' => 'nullable|string|max:500',
        ];
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
            'array' => 'El campo :attribute debe ser un arreglo.',
            'min' => 'El campo :attribute debe tener al menos :min elementos.',
            'string' => 'El campo :attribute debe ser una cadena de caracteres.',
            'max' => 'El campo :attribute debe contener máximo :max caracteres.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $course = Course::find($this->titulo);

            if (!$course) {
                $validator->errors()->add('titulo', 'El curso seleccionado no existe.');
                return;
            }

            // Validate total duration does not exceed course duration
            $totalMinutes = 0;
            foreach ($this->sessions as $session) {
                $start = Carbon::parse($session['start_time']);
                $end = Carbon::parse($session['end_time']);
                $totalMinutes += $start->diffInMinutes($end);
            }
            $totalHours = $totalMinutes / 60;

            if ($totalHours > $course->duration) {
                $validator->errors()->add('sessions', 'La duración total de las sesiones (' . $totalHours . ' horas) excede la duración del curso (' . $course->duration . ' horas).');
            }

            // Validate no overlapping sessions at the same location
            foreach ($this->sessions as $i => $session) {
                foreach ($this->sessions as $j => $other) {
                    if ($i >= $j) continue;

                    if ($session['location_id'] === $other['location_id'] &&
                        $session['session_date'] === $other['session_date'] &&
                        $session['start_time'] < $other['end_time'] &&
                        $session['end_time'] > $other['start_time']) {
                        $validator->errors()->add('sessions', 'Hay sesiones superpuestas en la ubicación ' . $session['location_id'] . ' el día ' . $session['session_date'] . '.');
                        return;
                    }
                }
            }

            // Validate no conflicts with existing sessions in the system
            foreach ($this->sessions as $session) {
                $conflict = CourseSession::where('location_id', $session['location_id'])
                    ->where('session_date', $session['session_date'])
                    ->where('start_time', '<', $session['end_time'])
                    ->where('end_time', '>', $session['start_time'])
                    ->whereNull('deleted_at')
                    ->count();

                if ($conflict > 0) {
                    $validator->errors()->add('sessions', 'La ubicación ' . $session['location_id'] . ' ya tiene una sesión programada el día ' . $session['session_date'] . ' en este horario.');
                    return;
                }
            }
        });
    }
}
