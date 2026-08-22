<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Scheduled;
use App\Models\Participant;
use App\Models\ParticipantStatus;
use App\Models\Funciones;
use App\Models\IdType;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;

use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\UserForm;
use Illuminate\Support\Facades\Auth;

class ParticipanteCursoController extends Controller
{
    public function getAllPorCurso($id)
    {
        $user = Auth::user();

        if ($user->cannot('getAllPorCurso', Participant::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $cursoprogramado = Scheduled::with(['course.capacity'])->find($id);

        if (!$cursoprogramado) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "El curso programado seleccionado no existe."));
        }

        $participantes = Participant::with(['person', 'participantStatus'])
            ->where('scheduled_id', $id);

        $puedeEvaluar = $user->can('evaluate', $cursoprogramado)
            && ($cursoprogramado->isEnCurso() || $cursoprogramado->isCulminado());

        return view('pages.admin.participantescursos.index')
            ->with('participantes', $participantes->paginate(10))
            ->with('cursoprogramado', $cursoprogramado)
            ->with('statuses', ParticipantStatus::orderBy('id')->get())
            ->with('canEvaluate', $puedeEvaluar)
            ->with('types', IdType::all());
    }//getAllPorCurso

    public function store(UserForm $request, $id)
    {
        $user = Auth::user();
        if ($user->cannot('store', Participant::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $cursoprogramado = Scheduled::find($id);
        if (!$cursoprogramado) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "El curso programado seleccionado no existe."));
        }
        if ($cursoprogramado->atMaxCapacity()) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "El curso programado se encuentra a su máxima capacidad."));
        }
        if (!$cursoprogramado->isPorDictar()) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "No se pueden registrar participantes en una acción de formación que no esté por dictar."));
        }

        $usuario = new User([
            'role_id' => Role::PARTICIPANTE,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $personData = array(
            'name' => $request->nombre,
            'last_name' => $request->apellido,
            'id_number' => $request->ci,
            'id_type_id' => $request->id_type,
            'sex' => $request->sex,
            'phone' => $request->phone,
        );
        $person = Person::create($personData);

        if (!$person) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "No se pudo crear el participante"));
        }
        $person->user()->save($usuario);

        $participant = new Participant([
            'participant_status_id' => Participant::PORINICIAR,
            'scheduled_id' => $id
        ]);

        if (today() >= $cursoprogramado->start_date) {
            $participant->participant_status_id = Participant::ENCURSO;
        }

        $response = $person->participant()->save($participant);

        if (!$response) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "El participante ha sido creado exitosamente, pero ha ocurrido un error al intentar registrarlo en la acción de formación. Intente asignarlo a través de la opción asignar participante"));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));
    }


    public function asignarParticipante(Request $request, $id)
    {
        $this->validate($request, [
            'participante' => 'required|integer|exists:people,id',
            'curso_p_id' => 'required|integer|exists:scheduled_course,id',
        ]);

        $user = Auth::user();
        if ($user->cannot('store', Participant::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        if ((int)$id !== (int)$request->curso_p_id) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "La acción de formación indicada no coincide con la solicitud."));
        }

        $cursoprogramado = Scheduled::find($request->curso_p_id);

        if (!$cursoprogramado->isPorDictar()) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "No se pueden asignar participantes a una acción de formación que no esté por dictar."));
        }

        if ($cursoprogramado->atMaxCapacity()) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "El curso programado se encuentra a su máxima capacidad."));
        }

        $yaAsignado = Participant::where('scheduled_id', $request->curso_p_id)
            ->where('person_id', $request->participante)
            ->exists();

        if ($yaAsignado) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "El participante ya se encuentra asignado a esta acción de formación."));
        }

        $participantecurso = new Participant();
        $participantecurso->participant_status_id = Participant::PORINICIAR;
        $participantecurso->scheduled_id = $request->curso_p_id;
        $participantecurso->person_id = $request->participante;

        if (today() >= $cursoprogramado->start_date) {
            $participantecurso->participant_status_id = Participant::ENCURSO;
        }

        if (!$participantecurso->save()) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "El participante no pudo ser asignado. Intente nuevamente"));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'estado' => 'required|integer|exists:participant_status,id',
        ]);

        $participantecurso = Participant::find($id);

        if (!$participantecurso) {
            return $this->statusUpdateResponse($request, "danger", "Error al intentar editar", "El participante seleccionado no existe.");
        }

        if (Auth::user()->cannot('updateStatus', $participantecurso)) {
            return $this->statusUpdateResponse($request, "danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion.");
        }

        $participantecurso->participant_status_id = $validated['estado'];

        if (!$participantecurso->save()) {
            return $this->statusUpdateResponse($request, "danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos.");
        }

        return $this->statusUpdateResponse($request, "success", "Editado exitosamente", "Operación exitosa.");
    }

    private function statusUpdateResponse(Request $request, string $type, string $title, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $type === "success",
                'title' => $title,
                'message' => $message,
            ], $type === "success" ? 200 : 403);
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert($type, $title, $message));
    }

    public function delete($id)
    {
        $user = Auth::user();
        if ($user->cannot('delete', Participant::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $particpantecurso = Participant::find($id);

        if ($particpantecurso == null) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al intentar eliminar", "El participante seleccionado no existe."));
        }

        if ($particpantecurso->delete()) {
            return Redirect::back()->with('alert', Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));
        }

        return Redirect::back()
            ->with('alert', Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));
    }
}
