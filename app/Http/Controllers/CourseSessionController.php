<?php

namespace App\Http\Controllers;

use App\Models\Scheduled;
use App\Models\CourseSession;
use App\Models\Location;
use App\Models\Funciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\CourseSessionForm;
use Illuminate\Support\Facades\Auth;

class CourseSessionController extends Controller
{
    public function index($id)
    {
        $user = Auth::user();
        $scheduled = Scheduled::with(['course', 'facilitator', 'courseStatus', 'sessions.location'])->find($id);

        if (!$scheduled || $user->cannot('getAll', Scheduled::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $locations = Location::orderBy('name', 'asc')->get();

        return view('pages.admin.cursosprogramados.sessions')
            ->with('scheduled', $scheduled)
            ->with('locations', $locations);
    }

    public function data($id)
    {
        $user = Auth::user();
        $scheduled = Scheduled::with('sessions.location')->find($id);

        if (!$scheduled || $user->cannot('getAll', Scheduled::class)) {
            return response()->json([]);
        }

        $sessions = $scheduled->sessions->map(function ($session) {
            $color = '#5bc0de';
            if ($session->status === 'completed') {
                $color = '#5cb85c';
            } elseif ($session->status === 'cancelled') {
                $color = '#d9534f';
            }

            return [
                'id' => $session->id,
                'title' => $session->location->name . ($session->notes ? ' - ' . $session->notes : ''),
                'start' => $session->session_date . 'T' . $session->start_time,
                'end' => $session->session_date . 'T' . $session->end_time,
                'color' => $color,
                'status' => $session->status,
                'location_id' => $session->location_id,
                'location_name' => $session->location->name,
                'notes' => $session->notes,
            ];
        });

        return response()->json($sessions);
    }

    public function store(CourseSessionForm $request, $id)
    {
        $user = Auth::user();

        if ($user->cannot('getAll', Scheduled::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $scheduled = Scheduled::find($id);

        if (!$scheduled) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "El curso programado no existe."));
        }

        $session = new CourseSession();
        $session->scheduled_course_id = $id;
        $session->location_id = $request->ubicacion;
        $session->session_date = $request->fecha_sesion;
        $session->start_time = $request->hora_inicio;
        $session->end_time = $request->hora_fin;
        $session->notes = $request->notas;

        if ($session->save()) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("success", "Sesión Creada Exitosamente", "Operación Exitosa."));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al Intentar Crear Sesión", "Operación Errónea."));
    }

    public function update(CourseSessionForm $request, $sessionId)
    {
        $user = Auth::user();

        if ($user->cannot('getAll', Scheduled::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $session = CourseSession::find($sessionId);

        if (!$session) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "La sesión no existe."));
        }

        $session->location_id = $request->ubicacion;
        $session->session_date = $request->fecha_sesion;
        $session->start_time = $request->hora_inicio;
        $session->end_time = $request->hora_fin;
        $session->notes = $request->notas;

        if ($session->save()) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("success", "Sesión Editada Exitosamente", "Operación Exitosa."));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al Intentar Editar Sesión", "Operación Errónea."));
    }

    public function destroy($sessionId)
    {
        $user = Auth::user();

        if ($user->cannot('getAll', Scheduled::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $session = CourseSession::find($sessionId);

        if (!$session) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "La sesión no existe."));
        }

        if ($session->delete()) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("success", "Sesión Eliminada Exitosamente", "Operación Exitosa."));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al Intentar Eliminar Sesión", "Operación Errónea."));
    }
}
