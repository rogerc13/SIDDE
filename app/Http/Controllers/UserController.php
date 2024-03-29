<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use App\Models\Person;
use App\Models\User;
use App\Models\Role;
use App\Models\Funciones;

use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\UserForm;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function get($id)
    {
        $user = Auth::user();
        $usuario = User::with('person')->find($id);
        if(!$usuario || $user->cannot('get', User::class))
        {
            return json_encode([]);
        }

        return json_encode($usuario);
    }

    public function getAll()
    {

        $user = Auth::user();

        if($user->cannot('getAll',User::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $nombres = filter_input(INPUT_GET,'nombres',FILTER_SANITIZE_STRING);
        $apellidos = filter_input(INPUT_GET,'apellidos',FILTER_SANITIZE_STRING);
        $cis = filter_input(INPUT_GET,'cis',FILTER_SANITIZE_STRING);
        $busqueda_rol = filter_input(INPUT_GET,'busqueda_rol',FILTER_SANITIZE_NUMBER_INT);

        $users = User::with('role')->with('person')->orderBy("id","asc");
        
        if($nombres)
           $users = User::whereHas('person',function($query) use($nombres){
                return $query->where('name','LIKE',"%$nombres%");
           });
        if($apellidos)
            $users = User::whereHas('person', function ($query) use ($apellidos) {
                return $query->where('last_name', 'LIKE', "%$apellidos%");
            });
        if($cis)
            
            $users = User::whereHas('person', function ($query) use ($cis) {
                return $query->where('id_number', 'LIKE', "%$cis%");
            });
        if($busqueda_rol)
           $users=$users->where('role_id','=',$busqueda_rol);

       	$roles = Role::$roles;

        return view('pages.admin.usuarios.index')
                ->with('users',$users->paginate(10))
                ->with('roles',$roles)
                ->with('nombres',$nombres)
                ->with('apellidos',$apellidos)
                ->with('cis',$cis)
                ->with('busqueda_rol',$busqueda_rol);

    }

    public function store(UserForm $request)
    {

        $user = Auth::user();
        if($user->cannot('store',User::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $usuario = new User([
            'role_id' => $request->rol,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $personData = array(
            'name' => $request->nombre,
            'last_name' => $request->apellido,
            'id_number' => $request->ci,
        );
        $person = Person::create($personData);

        if($person){
            $person->user()->save($usuario);
            return Redirect::back()
                    ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));

        }
        else {
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Crear usuario", "Operacion Erronea."));

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
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El Usuario no existe."));


        if ($user->cannot('update', User::class))
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));

        $usuario->email = $request->email;
        $usuario->role_id = $request->rol;
        $usuario->save();

        $usuario->person()->update([
            'name' => $request->nombre,
            'last_name' => $request->apellido,
            'id_number' => $request->ci
        ]);

        if($request->password!=''){
            $usuario->password = bcrypt($request->password);
        }
            

        if(!$usuario->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));


        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));


    }

    public function delete($id)
    {

        $user = Auth::user();
        if($user->cannot('delete',User::class))
        {
             return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $usuario = User::find($id);
        if($usuario==null)
        {
            return Redirect::back()
               ->with("alert",Funciones::getAlert("danger", "Error al eleminar", "Usuario no encontrado."));
        }

        if($usuario->isFacilitador()){
            if ($usuario->person->facilitator->scheduled->count() > 0) {
                return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Este facilitador se encuentra asignado a una acción de formación."));
            }
        }
        else if($usuario->isParticipante()){
            if ($usuario->person->participant->count() > 0) {
                return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Este participante se encuentra registrado en una acción de formación."));
            }
        }
        else if($usuario->isAdministrador()){
            return Redirect::back()->with('alert', Funciones::getAlert("danger", "Error", "No se puede eliminar a un administrador."));
        }

        if ($usuario->delete()) {
            $usuario->person->delete();
            return Redirect::back()->with('alert',Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));

        }

        return Redirect::back()
                ->with('alert',Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));

    }


    public function misDatos()
    {
        $user=Auth::user();

        if($user->cannot('misDatos',User::class))
            return redirect()->back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar obtener datos", "No tienes permisos para acceder a esta información."));

        return view('pages.admin.usuarios.misdatos');
    }

    public function misDatosUpdate(UserForm $request)
    {

        $user=Auth::user();

        if($user->cannot('misDatosUpdate',User::class))
            return redirect()->back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar obtener datos", "No tienes permisos para acceder a esta información."));

        $usuario = User::find($request->user_id);

        if (!$usuario)
           return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Este usuario no existe."));

        $usuario->email = $request->email;
        $usuario->save();

        if (null != $request->file('imagen')) {
            $request->file('imagen')->storeAs('public/avatars', $request->ci . '.' . $request->file('imagen')->getClientOriginalExtension());
            $avatar_path = $request->ci.'.'.$request->file('imagen')->getClientOriginalExtension();
            asset($avatar_path);
        }
        
        $usuario->person()->update([
            'name' => $request->nombre,
            'last_name' => $request->apellido,
            'id_number' => $request->ci,
            'sex' => $request->sex,
            'phone' => $request->phone,
            'avatar_path' => isset($avatar_path) ? $avatar_path : $usuario->person->avatar_path
        ]);

        if($request->password != ''){
            $usuario->password = bcrypt($request->password);
        }

            
        if(!$usuario->save()){
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));
        }
        
        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
    }
}
