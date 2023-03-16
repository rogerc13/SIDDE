<?php

namespace App\Http\Controllers;

use App\Funciones as AppFunciones;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\Categoria;
use App\Models\Funciones;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\CategoriaForm;
use Illuminate\Support\Facades\Auth;

class CategoriaController extends Controller
{
    public function get($id)
    {
        $user = Auth::user();
        $categoria = Categoria::find($id);
        if(!$categoria || $user->cannot('get', Categoria::class))
        {
            return json_encode([]);
        }
        
        return json_encode($categoria);
    }
    
    public function getAll()
    {
        $user = Auth::user();
        
        if($user->cannot('getAll', Categoria::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
            
        }
        
        $categorias = Categoria::orderBy("nombre","asc")->paginate(10);
        return view('pages.admin.categorias.index')->with('categorias',$categorias);
    }

    
    public function store(CategoriaForm $request)
    {
       

        $user=Auth::user();    
       
        if ($user->can('store', Categoria::class)){              
            
            
            $categoria = new Categoria();
            $categoria->nombre = $request->nombre;            

            if($categoria->save()){            
                return Redirect::back()
                        ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));
                
            }
            
                
            return Redirect::back()
               ->with("alert",Funciones::getAlert("danger", "Error al Intentar Crear Curso", "Operacion Erronea."));
            
        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));       
    }
    
    public function count()
    {
 
        
    }

    public function update(CategoriaForm $request, $id)
    {
        
        $user=Auth::user();

        $categoria = Categoria::find($id);
        
        if (!$categoria)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El área seleccionada no pudo ser encontrada."));
        
        if ($user->cannot('update',Categoria::class))
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));
        

        
        $categoria->nombre = $request->nombre;
        
        if(!$categoria->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));     

    
        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
    }
    
    public function delete($id)
    {
        $user=Auth::user();
        
        if ($user->can('delete', Categoria::class)) 
        {   
            $categoria = Categoria::find($id);
            if($categoria==null)
            {
                return Redirect::back()
                    ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El área seleccionada no pudo ser encontrada."));
                
            }


            if($categoria->cursos->count() > 0){
                return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Esta categoria no puede ser eliminada, poseé cursos asignados."));
            }
           

            if ($categoria->delete()) {          
                                       
                return Redirect::back()->with('alert',Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));
                
            }

            return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));
            
        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Editar", "No tienes permisos para realizar esta accion."));
          
    }
}
