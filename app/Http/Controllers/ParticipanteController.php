<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use App\Models\Funciones;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\UserForm;
use Illuminate\Support\Facades\Auth;

class ParticipanteController extends Controller
{
    public function get($id)
    {

        $user = Auth::user();
        $usuario = User::find($id);
        if(!$usuario || $user->cannot('getParticipante', User::class))
        {
            return json_encode([]);
        }
        
        return json_encode($usuario);
    }
    
    public function getAll()
    {

        $user = Auth::user();

        if($user->cannot('getAllParticipante',User::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }
 
        $nombres = filter_input(INPUT_GET,'nombres',FILTER_SANITIZE_STRING);
        $apellidos = filter_input(INPUT_GET,'apellidos',FILTER_SANITIZE_STRING);
        $cis = filter_input(INPUT_GET,'cis',FILTER_SANITIZE_STRING);  



        $users = User::where('role_id','5');

        if($nombres)
           $users=$users->where('nombre','LIKE',"%$nombres%");
        if($apellidos)
           $users=$users->where('apellido','LIKE',"%$apellidos%");        
        if($cis)
           $users=$users->where('ci','=',$cis);


        
        return view('pages.admin.participantes.index')
                ->with('users',$users->paginate(10))
                ->with('nombres',$nombres)
                ->with('apellidos',$apellidos)
                ->with('cis',$cis);;

    }
    
    public function store(UserForm $request)
    {
       
        $user = Auth::user();
        if($user->cannot('storeParticipante',User::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $usuario = new User();
        $usuario->nombre = $request->nombre;
        $usuario->apellido = $request->apellido;
        $usuario->email = $request->email;
        $usuario->ci = $request->ci;
        $usuario->rol_id = 5;
        $usuario->password = bcrypt($request->password);

        if($usuario->save()){
            return Redirect::back()
                    ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));

        }
        else {            
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Crear Curso", "Operacion Erronea."));

        }

    }
    
    public function count()
    {
        //
    }

    public function update(UserForm $request, $id)
    {
        $user=Auth::user();

        $usuario = User::find($id);
        
        if (!$usuario)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El curso ingresada no existe."));
 
        
        if ($user->cannot('updateParticipante', User::class))
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));
        

        $usuario->nombre = $request->nombre;
        $usuario->apellido = $request->apellido;
        $usuario->email = $request->email;
        $usuario->ci = $request->ci;

        if($request->password!='')
            $usuario->password = bcrypt($request->password);       
        
        if(!$usuario->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));
    
        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
    }
    
    public function delete($id)
    {

        $user = Auth::user();
        if($user->cannot('deleteParticipante',User::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $usuario = User::where('id',$id)
                        ->where('rol_id','5')
                        ->first();
    
        if($usuario==null)
        {
            return Redirect::back()
               ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Area ingresada no encontrada."));
        }

        if($usuario->cursosParticipante->count() > 0){
            return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Este participante se encuentra registrado en una acción de formación."));
        }

        if ($usuario->delete()) {              
          
            return Redirect::back()->with('alert',Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));

        }

        return Redirect::back()
                ->with('alert',Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));

    }
}
