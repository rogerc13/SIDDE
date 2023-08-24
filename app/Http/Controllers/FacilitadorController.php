<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Person;
use App\Models\Funciones;
use App\Models\IdType;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\UserForm;
use App\Models\Facilitator;

class FacilitadorController extends Controller
{
    public function get($id)
    {
        $user = Auth::user();
        $usuario = User::with('person')->find($id);
        if(!$usuario || $user->cannot('getFacilitador', User::class))
        {
            return json_encode([]);
        }
        
        return json_encode($usuario);
    }
    
    public function getAll()
    {

        $user = Auth::user();

        if($user->cannot('getAllFacilitador',User::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $nombres = filter_input(INPUT_GET,'nombres',FILTER_SANITIZE_STRING);
        $apellidos = filter_input(INPUT_GET,'apellidos',FILTER_SANITIZE_STRING);
        $cis = filter_input(INPUT_GET,'cis',FILTER_SANITIZE_STRING);        
        $idTypeId = filter_input(INPUT_GET, 'id_type_search',FILTER_SANITIZE_NUMBER_INT);        

        $users = User::with('role')->with('person')->where('role_id','4');
        
        if($nombres)
           $users=$users->whereHas('person',function($query) use($nombres){
                $query->where('name','LIKE',"%$nombres%");
           });
        if($apellidos)
        $users = $users->whereHas('person', function ($query) use ($apellidos) {
            $query->where('last_name', 'LIKE', "%$apellidos%");
        });
        if($cis)
        $users = $users->whereHas('person', function ($query) use ($cis,$idTypeId) {
            $query->where('id_type_id','=',$idTypeId)->where('id_number', 'LIKE' , "%$cis%");
        });


        $types = IdType::all();
        return view('pages.admin.facilitadores.index')
                ->with('users',$users->paginate(10))
                ->with('nombres',$nombres)
                ->with('apellidos',$apellidos)
                ->with('cis',$cis)->with('id_type_search',$idTypeId)->with('types',$types);

    }
    
    public function store(UserForm $request)
    {
       
        $user = Auth::user();
        if($user->cannot('storeFacilitador',User::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $usuario = new User([
            'role_id' => 4,
            'email' => $request->email,
            'password' => bcrypt($request->password) 
        ]);
        $personData = array(
            'name' => $request->nombre,
            'last_name' => $request->apellido,
            'id_number' => $request->ci,
            'id_type_id' => $request->id_type,
        );
        
        $person = Person::create($personData);

        $facilitator = new Facilitator([]);
        if($person){
            $person->user()->save($usuario);
            $person->facilitator()->save($facilitator);
            
            //NEED TO CREATE FACILITATOR
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
 
        
        if ($user->cannot('updateFacilitador', User::class))
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));


        $usuario->email = $request->email;
        $usuario->save();
        $usuario->person()->update([
            'name' => $request->nombre,
            'last_name' => $request->apellido,
            'id_number' => $request->ci,
            'id_type_id' => $request->id_type
        ]);

        
        

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
        if($user->cannot('deleteFacilitador',User::class))
        {
            return Redirect::back()
                     ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $usuario = User::where('id',$id)
                        ->where('role_id','4')
                        ->first();
    
        if($usuario==null)
        {
            return Redirect::back()
               ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Area ingresada no encontrada."));

        }

        if($usuario->person->facilitator->scheduled->count() > 0){
            return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Este facilitador se encuentra asignado a una acción de formación."));
        }


        if ($usuario->delete()) {
            //needs to also delete on facilitator table              
            return Redirect::back()->with('alert',Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));
        }


        return Redirect::back()
                ->with('alert',Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));

    }
}
