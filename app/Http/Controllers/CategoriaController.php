<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Funciones;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\CategoriaForm;
use Illuminate\Support\Facades\Auth;

class CategoriaController extends Controller
{
    public function get($id)
    {
        $user = Auth::user();
        $categoria = Category::find($id);
        if(!$categoria || $user->cannot('get', Category::class))
        {
            return json_encode([]);
        }
        
        return json_encode($categoria);
    }
    
    public function getAll(Request $request)
    {
        $user = Auth::user();
        if($user->cannot('getAll', Category::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
            
        }
        $categorias = Category::orderBy("name","asc");
        $name = $request->input('name');
        if($name)
            {
                $categorias = $categorias->where("name","LIKE","%$name%");
            }
        
        $categorias = $categorias->paginate(10);
        return view('pages.admin.categorias.index')
        ->with('categorias',$categorias)
        ->with('name', $name);
    }

    
    public function store(CategoriaForm $request)
    {
       

        $user=Auth::user();    
       
        if ($user->can('store', Category::class)){              
            
            
            $categoria = new Category();
            $categoria->name = $request->nombre;            

            if($categoria->save()){            
                return Redirect::back()
                        ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));
                
            }
            
                
            return Redirect::back()
               ->with("alert",Funciones::getAlert("danger", "Error al Intentar Crear Categoria", "Operacion Erronea."));
            
        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));       
    }

    public function update(CategoriaForm $request, $id)
    {
        
        $user=Auth::user();

        $categoria = Category::find($id);
        
        if (!$categoria)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El área seleccionada no pudo ser encontrada."));
        
        if ($user->cannot('update',Category::class))
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));
        

        
        $categoria->name = $request->nombre;
        
        if(!$categoria->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));     

    
        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
    }
    
    public function delete($id)
    {
        $user=Auth::user();
        
        if ($user->can('delete', Category::class)) 
        {   
            $categoria = Category::find($id);
            if($categoria==null)
            {
                return Redirect::back()
                    ->with("alert",Funciones::getAlert("danger", "Error al intentar eliminar", "El área seleccionada no pudo ser encontrada."));
                
            }


            if($categoria->courses->count() > 0){
                return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Esta categoria no puede ser eliminada, posee cursos asignados."));
            }
           

            if ($categoria->delete()) {          
                                       
                return Redirect::back()->with('alert',Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));
                
            }

            return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));
            
        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Eliminar", "No tienes permisos para realizar esta accion."));
          
    }
}
