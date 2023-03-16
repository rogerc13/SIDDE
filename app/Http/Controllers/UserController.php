<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use App\Models\Rol;
use App\Models\Funciones;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\UserForm;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function get($id)
    {
        $user = Auth::user();
        $usuario = User::find($id);
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


       // $users = User::where('rol_id','>','0')
       // 			->orderBy("id","asc");
        $users = User::with('rol')
       			->orderBy("id","asc");

        if($nombres)
           $users=$users->where('nombre','LIKE',"%$nombres%");
        if($apellidos)
           $users=$users->where('apellido','LIKE',"%$apellidos%");
        if($cis)
           $users=$users->where('ci','=',$cis);
        if($busqueda_rol)
           $users=$users->where('rol_id','=',$busqueda_rol);

       	$roles = Rol::$roles;


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

        $usuario = new User();
        $usuario->nombre = $request->nombre;
        $usuario->apellido = $request->apellido;
        $usuario->email = $request->email;
        $usuario->ci = $request->ci;
        $usuario->rol_id = $request->rol;
        $usuario->password = bcrypt($request->password);

        if($usuario->save()){
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



        $usuario->nombre = $request->nombre;
        $usuario->apellido = $request->apellido;
        $usuario->email = $request->email;
        $usuario->ci = $request->ci;
        $usuario->rol_id = $request->rol;

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
        if($user->cannot('delete',User::class))
        {
             return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $usuario = User::find($id);
        if($usuario==null)
        {
            return Redirect::back()
               ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Area ingresada no encontrada."));

        }

        if($usuario->isFacilitador()){
            if($usuario->cursoFacilitador->count() > 0){
                return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Este facilitador se encuentra asignado a una acción de formación."));
            }
        }
        else if($usuario->isParticipante()){
            if($usuario->cursosParticipante->count() > 0){
                return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Este participante se encuentra registrado en una acción de formación."));
            }
        }

        if ($usuario->delete()) {

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



        $path = base_path() . '/public/uploads/perfiles';
        // $path = 'App/public/uploads/perfiles';

         //^ "/var/www/html/app/Http/Controllers/fejV21"
        // dd(tempnam($path, ""));



        /*  */
        if($request->file('imagen')){
            $archivoAnterior=$user->imagen;

            $arhcivotemporal=tempnam($path, "");
            $infotemporal= pathinfo($arhcivotemporal);
            $nombertemporal= $infotemporal['filename'];
            $info = pathinfo($request->file('imagen')->getClientOriginalName());
            $extension = $info['extension'];
            $docTempName='perfil_'.$nombertemporal.".".$extension;
            unlink($arhcivotemporal);

            $usuario->imagen = $docTempName;
        }

        $usuario->nombre = $request->nombre;
        $usuario->apellido = $request->apellido;
        $usuario->email = $request->email;
        $usuario->ci = $request->ci;

        if($request->password!='')
            $usuario->password = bcrypt($request->password);

        if(!$usuario->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));


        if($request->file('imagen')){

            if($archivoAnterior){
               // $path2 = 'App/public/uploads/perfiles'.$archivoAnterior;
                $path2 = base_path() .'/public/uploads/perfiles/'.$archivoAnterior;
                if(file_exists($path2))
                {
                    unlink($path2);
                }
            }


            $request->file('imagen')->move($path, $docTempName);
        }


        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));


    }
}
