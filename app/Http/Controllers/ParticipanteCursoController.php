<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Scheduled;
use App\Models\Course;
use App\Models\Person;
use App\Models\User;
use App\Models\Participant;
use App\Models\ParticipantStatus;
use App\Models\Funciones;

use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\UserForm;
use Illuminate\Support\Facades\Auth;

class ParticipanteCursoController extends Controller
{
    public function get($participanteId)
    {

        $user = Auth::user();
        $participantecurso = Participant::find($participanteId);
        if(!$participantecurso || $user->cannot('get', Participant::class))
        {
            return json_encode([]);
        }
        return json_encode($participantecurso);
    }
    
    public function getAll()
    {

        $user = Auth::user();

        if($user->cannot('getAll',Participant::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }
         
        return view('pages.admin.participantes.index')
                ->with('users',$users->paginate(10));

    }

    public function getAllPorCurso($id)
    {

        $user = Auth::user();

        if($user->cannot('getAllPorCurso',Participant::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $cursoprogramado = Scheduled::find($id);

        if (!$cursoprogramado)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "El curso programado seleccionado no existe."));

        $participantes = Participant::with('person')->where('scheduled_id',$id);
        $pts = $participantes->pluck('person_id');        


        $participantes2 = User::where('role_id','5')
                    ->whereNotIn('person_id',$pts)
                    ->get();


        
        return view('pages.admin.participantescursos.index')
                ->with('participantes',$participantes->paginate(10))
                ->with('cursoprogramado',$cursoprogramado)
                ->with('participantes2',$participantes2)
                ->with('estados', Participant::$estados)->with('statuses',ParticipantStatus::all());

    }//getAllPorCurso

    public function getAllEvaluation($id){
        $user = Auth::user();

        if ($user->cannot('getAllPorCurso', Participant::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $cursoprogramado = Scheduled::find($id);

        if (!$cursoprogramado)
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "El curso programado seleccionado no existe."));

        $participants = Participant::with('person')->withTrashed()->where('scheduled_id', $id);
        $pts = $participants->pluck('person_id');


        $participantes2 = User::where('role_id', '5')
        ->whereNotIn('person_id', $pts)
        ->get();

        return view('pages.admin.usuarios.evaluation')
        ->with('participants', $participants->paginate(10))
            ->with('cursoprogramado', $cursoprogramado)
            ->with('participantes2', $participantes2)
            ->with('statuses', ParticipantStatus::all());
    }//end get all evaluation

    public function participantEvaluationStatus(Request $request){
        
        //return json_encode($request->data[0]['courseid']);
        //return json_encode(count($request->data));
        //$i = 0;
        foreach ($request->data as $key => $value) {
            $data = array('id' => $request->data[$key]['participant_id'],
                    'participant_status_id' => $request->data[$key]['status_id']);

            Participant::where('scheduled_id',$request->data[$key]['scheduled_id'])->update($data);
        } 
        return json_encode($response = array('success'=>'Evaluacion Completada','error'=>'Error durante la evaluación'));
    }//end participantEvaluationStatus

    public function store(UserForm $request,$id)
    {

      
        $user = Auth::user();
        if($user->cannot('store',Participant::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
        }


        $cursoprogramado = Scheduled::find($id);
        if (!$cursoprogramado)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "El curso programado seleccionado no existe."));

        $usuario = new User([
            'role_id' => 5,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $personData = array(
            'name' => $request->nombre,
            'last_name' => $request->apellido,
            'id_number' => $request->ci,
        );
        $person = Person::create($personData);
        //$success = $person->save();
        //dd($person);
        if(!$person){
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "No se pudo crear el participante"));
        }
        $person->user()->save($usuario);

        $participant = new Participant([
            'participant_status_id' => 1,
            'scheduled_id' => $id
        ]);

        $response = $person->participant()->save($participant);

        if(!$response){
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "El participante ha sido creado exitosamente, pero ha ocurrido un error al intentar registrarlo en la acción de formación. Intente asignarlo a través de la opción asignar participante"));
        }


        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));

    }


    public function asignarParticipante(Request $request,$id)
    {

        $this->validate($request, [
            'participante' => 'required|integer|exists:people,id',
            'curso_p_id' => 'required|integer|exists:scheduled_course,id',
        ]);


        $user = Auth::user();
        if($user->cannot('store',Participant::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
        }
        
        $validacion= Participant::where('scheduled_id',$request->curso_p_id)
                                        ->where('person_id',$request->participante)
                                        ->count();
       //dd($request->participante);
        /* if($validacion > 0)
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al asignar","Este participante seleccionado ya se encuentra asignado."));
        } */ 
   
        $participantecurso = new Participant();
        $participantecurso->participant_status_id=1;
        $participantecurso->scheduled_id=$request->curso_p_id;
        $participantecurso->person_id=$request->participante;

        if(!$participantecurso->save()){
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "El participante no pudo ser asignado. Intente nuevamente"));
        }

        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));

    }
    
    public function count()
    {
        //
    }

    public function update(Request $request, $id)
    {

        $user=Auth::user();

        $participantecurso = Participant::find($id);
        
        if (!$participantecurso)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El participante seleccionado no existe."));
 
        
        if ($user->cannot('update', Participant::class))
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));
        

        $participantecurso->participant_status_id = $request->estado;

             
        if(!$participantecurso->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));
    
        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
    }
    
    public function delete($id)
    {
        
        $user = Auth::user();
        if($user->cannot('delete',Participant::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $particpantecurso = Participant::find($id);

    
        if($particpantecurso==null)
        {
            return Redirect::back()
               ->with("alert",Funciones::getAlert("danger", "Error al intentar eliminar", "El participante seleccionado no existe."));

        }


        if ($particpantecurso->delete()) {              
          
            return Redirect::back()->with('alert',Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));

        }

        return Redirect::back()
                ->with('alert',Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));

    }
}
