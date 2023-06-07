<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\CursoProgramado;
use App\Models\Curso;
use App\Models\User;
use App\Models\ParticipanteCurso;
use App\Models\Funciones;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\UserForm;
use Illuminate\Support\Facades\Auth;

class ParticipanteCursoController extends Controller
{
    public function get($participanteId)
    {

        $user = Auth::user();
        $participantecurso = ParticipanteCurso::find($participanteId);
        if(!$participantecurso || $user->cannot('get', ParticipanteCurso::class))
        {
            //$data = $participanteId;
            //$data = "not found";
            //return json_encode($user);
            return json_encode([]);
        }
        return json_encode($participantecurso);
    }
    
    public function getAll()
    {

        $user = Auth::user();

        if($user->cannot('getAll',ParticipanteCurso::class))
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

        if($user->cannot('getAllPorCurso',ParticipanteCurso::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $cursoprogramado = CursoProgramado::find($id);

        if (!$cursoprogramado)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "El curso programado seleccionado no existe."));

        $participantes = ParticipanteCurso::with('participante')->where('curso_programado_id',$id);
        $pts = $participantes->pluck('user_id');        


        $participantes2 = User::where('rol_id','5')
                    ->whereNotIn('id',$pts)
                    ->orderBy("nombre","asc")
                    ->get();


                
        return view('pages.admin.participantescursos.index')
                ->with('participantes',$participantes->paginate(10))
                ->with('cursoprogramado',$cursoprogramado)
                ->with('participantes2',$participantes2)
                ->with('estados', ParticipanteCurso::$estados);

    }
    
    public function store(UserForm $request,$id)
    {

      
        $user = Auth::user();
        if($user->cannot('store',ParticipanteCurso::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
        }


        $cursoprogramado = CursoProgramado::find($id);
        if (!$cursoprogramado)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "El curso programado seleccionado no existe."));


        $usuario = new User();
        $usuario->nombre = $request->nombre;
        $usuario->apellido = $request->apellido;
        $usuario->email = $request->email;
        $usuario->ci = $request->ci;
        $usuario->rol_id = 5;
        $usuario->password = bcrypt($request->password);


        if(!$usuario->save()){
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "No se pudo crear el participante"));
        }


        $participantecurso = new ParticipanteCurso();
        $participantecurso->estado=1;
        $participantecurso->curso_programado_id=$id;
        $participantecurso->user_id=$usuario->id;

        if(!$participantecurso->save()){
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "El participante ha sido creado exitosamente, pero ha ocurrido un error al intentar registrarlo en la acción de formación. Intente asignarlo a través de la opción asignar participante"));
        }


        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));

    }


    public function asignarParticipante(Request $request,$id)
    {


        $this->validate($request, [
            'participante' => 'required|integer|exists:users,id',
            'curso_p_id' => 'required|integer|exists:curso_programado,id',
        ]);


        $user = Auth::user();
        if($user->cannot('store',ParticipanteCurso::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
        }

        $validacion= ParticipanteCurso::where('curso_programado_id',$request->curso_p_id)
                                        ->where('user_id',$request->participante)
                                        ->count();
       
        if($validacion > 0)
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al asignar","Este participante seleccionado ya se encuentra asignado."));
        } 
   

     
        $participantecurso = new ParticipanteCurso();
        $participantecurso->estado=1;
        $participantecurso->curso_programado_id=$request->curso_p_id;
        $participantecurso->user_id=$request->participante;

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

        $participantecurso = ParticipanteCurso::find($id);
        
        if (!$participantecurso)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El participante seleccionado no existe."));
 
        
        if ($user->cannot('update', ParticipanteCurso::class))
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));
        

        $participantecurso->estado = $request->estado;

             
        if(!$participantecurso->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));
    
        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
    }
    
    public function delete($id)
    {

        $user = Auth::user();
        if($user->cannot('delete',ParticipanteCurso::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $particpantecurso = ParticipanteCurso::find($id);

    
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
