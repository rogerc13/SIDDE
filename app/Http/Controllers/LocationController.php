<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Funciones;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\LocationForm;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function get($id)
    {
        $user = Auth::user();
        $location = Location::find($id);
        if(!$location || $user->cannot('get', Location::class))
        {
            return json_encode([]);
        }
        
        return json_encode($location);
    }
    
    public function getAll()
    {
        $user = Auth::user();
        if($user->cannot('getAll', Location::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
            
        }
        $locations = Location::orderBy("name","asc");
        $name = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_STRING);
        if($name)
            {
                $locations = $locations->where("name","LIKE","%$name%");
            }
        
        $locations = $locations->paginate(10);
        return view('pages.admin.ubicaciones.index')
        ->with('locations',$locations)
        ->with('name', $name);
    }

    
    public function store(LocationForm $request)
    {

        $user=Auth::user();    
       
        if ($user->can('store', Location::class)){              
            
            
            $location = new Location();
            $location->name = $request->nombre;            

            if($location->save()){            
                return Redirect::back()
                        ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));
                
            }
            
                
            return Redirect::back()
               ->with("alert",Funciones::getAlert("danger", "Error al Intentar Crear Ubicación", "Operacion Erronea."));
            
        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));       
    }
    
    public function count()
    {
 
        
    }

    public function update(LocationForm $request, $id)
    {
        
        $user=Auth::user();

        $location = Location::find($id);
        
        if (!$location)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "La ubicación seleccionada no pudo ser encontrada."));
        
        if ($user->cannot('update',Location::class))
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));
        

        
        $location->name = $request->nombre;
        
        if(!$location->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));     
 
    
        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
    }
    
    public function delete($id)
    {
        $user=Auth::user();
        
        if ($user->can('delete', Location::class)) 
        {   
            $location = Location::find($id);
            if($location==null)
            {
                return Redirect::back()
                    ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "La ubicación seleccionada no pudo ser encontrada."));
                
            }


            if($location->sessions->count() > 0){
                return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Esta ubicación no puede ser eliminada, posee sesiones asignadas."));
            }
           

            if ($location->delete()) {          
                                       
                return Redirect::back()->with('alert',Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));
                
            }

            return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));
            
        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Editar", "No tienes permisos para realizar esta accion."));
          
    }
}
