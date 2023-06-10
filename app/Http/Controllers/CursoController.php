<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\Curso;
use App\Models\Categoria;
use App\Models\Funciones;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\CursoForm;
use Illuminate\Support\Facades\Auth;


use Illuminate\Support\Facades\DB;


class CursoController extends Controller
{
    public function get($id)
    {
        $user = Auth::user();
        $curso = Curso::find($id);
        if(!$curso || $user->cannot('get', Curso::class))
        {
            return json_encode([]);
        }

        return json_encode($curso);
    }

    public function getAccionFormacion($id)
    {
        $user = Auth::user();
        $curso = Curso::find($id);

        if($curso==null)
        {
            return Redirect::back()
                        ->with("alert", Funciones::getAlert("danger","Error","La acción de formación no pudo ser encontrada"));
        }

        //return view('pages.public.ficha')->with('curso',$curso);
        return view('pages.public.ficha_tecnica')->with('curso',$curso);
    }

    public function descargarDoc($id, $d)
    {

        $user = Auth::user();

        if($user->cannot('descargarDoc', Curso::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
        }

        $curso = Curso::find($id);

        if($curso==null)
        {
            return Redirect::back()
                        ->with("alert", Funciones::getAlert("danger","Error","La acción de formación no pudo ser encontrada"));
        }


        $path = base_path() . '/public/uploads/documentos/';

        if($d == 0){
            if($curso->ficha_tecnica){
                $path2 = base_path() .'/public/uploads/documentos/'.$curso->ficha_tecnica;

                if(file_exists($path2))
                {

                    return response()->download($path2,"Ficha tecnica.".pathinfo($curso->ficha_tecnica, PATHINFO_EXTENSION));

                }
            }

        }

        elseif ($d == 1) {
            if($curso->manual_p){
                $path2 = base_path() .'/public/uploads/documentos/'.$curso->manual_p;
                if(file_exists($path2))
                {
                    return response()->download($path2,"Manual de participante.".pathinfo($curso->manual_p, PATHINFO_EXTENSION));
                }
            }

        }
        elseif ($d == 2) {
            if($curso->manual_f){
                $path2 = base_path() .'/public/uploads/documentos/'.$curso->manual_f;
                if(file_exists($path2))
                {
                    return response()->download($path2,"Manual de facilitador.".pathinfo($curso->manual_f, PATHINFO_EXTENSION));
                }
            }
        }
        elseif ($d == 3) {
            if($curso->guia){
                $path2 = base_path() .'/public/uploads/documentos/'.$curso->guia;
                if(file_exists($path2))
                {
                    return response()->download($path2,"Guía de dotación".pathinfo($curso->guia, PATHINFO_EXTENSION));
                }
            }
        }
        elseif ($d == 4) {
             if($curso->presentacion){
                $path2 = base_path() .'/public/uploads/documentos/'.$curso->presentacion;
                if(file_exists($path2))
                {
                    return response()->download($path2,"Presentacion".pathinfo($curso->presentacion, PATHINFO_EXTENSION));
                }
            }
        }
        return Redirect::back();

    }

    public function getAll()
    {

        $user = Auth::user();

        if($user->cannot('getAll', Curso::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $titulos = filter_input(INPUT_GET,'titulos',FILTER_SANITIZE_STRING);
        $id_areas = filter_input(INPUT_GET,'id_areas',FILTER_SANITIZE_NUMBER_INT);


        $lista = $categorias = Categoria::orderBy("nombre","asc");
        $categorias = $lista->pluck('nombre','id');


        $cursos = Curso::orderBy("titulo","asc")->with('categoria');

        if($titulos)
           $cursos=$cursos->where('titulo','LIKE',"%$titulos%");

        if($id_areas)
           $cursos=$cursos->where('categoria_id','=',$id_areas);

        return view('pages.admin.cursos.index')
                ->with('cursos',$cursos->paginate(10))
                ->with('categorias',$lista->get())
                //->with('categorias',$categorias)
                ->with('titulos',$titulos)
                ->with('busqueda_area',$id_areas);
    }

/*
    public function store(CursoForm $request)
    {



        $user=Auth::user();

        if ($user->can('store', Curso::class)){


            $curso = new Curso();
            $curso->codigo = rand(1000,9999);
            $curso->titulo = $request->titulo;
            $curso->categoria_id = $request->categoria_id;
            $curso->modalidad = $request->modalidad;
            $curso->duracion = $request->duracion;
            $curso->dirigido = $request->dirigido;
            $curso->min = $request->min;
            $curso->max = $request->max;
            $curso->objetivo = $request->objetivo;
            $curso->contenido = $request->contenido;

            $path = base_path() . '/public/uploads/documentos';

            if($request->file('manual_p')){

                $arhcivotemporal_1=tempnam($path, "");
                $infotemporal_1= pathinfo($arhcivotemporal_1);
                $nombertemporal_1= $infotemporal_1['filename'];
                $info_1 = pathinfo($request->file('manual_p')->getClientOriginalName());
                $extension_1 = $info_1['extension'];
                $docTempName_1='m_p'.$nombertemporal_1.".".$extension_1;
                unlink($arhcivotemporal_1);

                $curso->manual_p = $docTempName_1;

            }
            if($request->file('manual_f')){

                $arhcivotemporal_2=tempnam($path, "");
                $infotemporal_2= pathinfo($arhcivotemporal_2);
                $nombertemporal_2= $infotemporal_2['filename'];
                $info_2 = pathinfo($request->file('manual_f')->getClientOriginalName());
                $extension_2 = $info_2['extension'];
                $docTempName_2='m_f'.$nombertemporal_2.".".$extension_2;
                unlink($arhcivotemporal_2);

                $curso->manual_f = $docTempName_2;
            }
            if($request->file('guia')){
                $arhcivotemporal_3=tempnam($path, "");
                $infotemporal_3= pathinfo($arhcivotemporal_3);
                $nombertemporal_3= $infotemporal_3['filename'];
                $info_3 = pathinfo($request->file('guia')->getClientOriginalName());
                $extension_3 = $info_3['extension'];
                $docTempName_3='g_'.$nombertemporal_3.".".$extension_3;
                unlink($arhcivotemporal_3);

                $curso->guia = $docTempName_3;

            }
            if($request->file('presentacion')){
                $arhcivotemporal_4=tempnam($path, "");
                $infotemporal_4= pathinfo($arhcivotemporal_4);
                $nombertemporal_4= $infotemporal_4['filename'];
                $info_4 = pathinfo($request->file('presentacion')->getClientOriginalName());
                $extension_4 = $info_4['extension'];
                $docTempName_4='p_'.$nombertemporal_4.".".$extension_4;
                unlink($arhcivotemporal_4);

                $curso->presentacion = $docTempName_4;

            }


            if($curso->save()){
                if($request->file('manual_p')){
                    $request->file('manual_p')->move($path, $docTempName_1);
                }
                if($request->file('manual_f')){
                    $request->file('manual_f')->move($path, $docTempName_2);
                }
                if($request->file('guia')){
                    $request->file('guia')->move($path, $docTempName_3);
                }
                if($request->file('presentacion')){
                   $request->file('presentacion')->move($path, $docTempName_4);
                }
                return Redirect::back()
                        ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));

            }


            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Crear Curso", "Operacion Erronea."));

        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));



    }
    */

    public function setCourse(Request $request){
        
        
        /*
            $user = Auth::user();
            if ($user->can('store', Curso::class)) {
                $data = array(
                    'codigo' => 9377747,
                    'titulo' => $request->titulo,
                    'categoria_id' => $request->categoria_id,
                    'modalidad' => $request->modalidad,
                    'objetivo'=> $request->objetivo,
                    //'contenido'  => $request->contenido,
                    'duracion' => $request->duracion,
                    'dirigido' => $request->dirigido,
                    'max'=> $request->max,
                    'min'=> $request->min,
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s')
                );

                
                $courseContent = array(
                    
                )

                $response = DB::table('course_content')->insert($courseContent)

                $response = DB::table('curso')->insert($data);
                return Redirect::back()->with("alert", Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));
            }
            return Redirect::back()->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));

            */
    }

    public function count()
    {


    }

    public function update(CursoForm $request, $id)
    {

        $user=Auth::user();

        $curso = Curso::find($id);

        if (!$curso)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El curso ingresada no existe."));


        if ($user->cannot('update', Curso::class))
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));



        $curso->titulo = $request->titulo;
        $curso->categoria_id = $request->categoria_id;
        $curso->modalidad = $request->modalidad;
        $curso->duracion = $request->duracion;
        $curso->dirigido = $request->dirigido;
        $curso->min = $request->min;
        $curso->max = $request->max;
        $curso->objetivo = $request->objetivo;
        $curso->contenido = $request->contenido;


        $path = base_path() . '/public/uploads/documentos';

        if($request->file('manual_p')){
            $archivoAnterior_1=$curso->manual_p;

            $arhcivotemporal_1=tempnam($path, "");
            $infotemporal_1= pathinfo($arhcivotemporal_1);
            $nombertemporal_1= $infotemporal_1['filename'];
            $info_1 = pathinfo($request->file('manual_p')->getClientOriginalName());
            $extension_1 = $info_1['extension'];
            $docTempName_1='m_p'.$nombertemporal_1.".".$extension_1;
            unlink($arhcivotemporal_1);

            $curso->manual_p = $docTempName_1;

        }
        if($request->file('manual_f')){
            $archivoAnterior_2=$curso->manual_f;

            $arhcivotemporal_2=tempnam($path, "");
            $infotemporal_2= pathinfo($arhcivotemporal_2);
            $nombertemporal_2= $infotemporal_2['filename'];
            $info_2 = pathinfo($request->file('manual_f')->getClientOriginalName());
            $extension_2 = $info_2['extension'];
            $docTempName_2='m_f'.$nombertemporal_2.".".$extension_2;
            unlink($arhcivotemporal_2);

            $curso->manual_f = $docTempName_2;
        }
        if($request->file('guia')){
            $archivoAnterior_3=$curso->guia;

            $arhcivotemporal_3=tempnam($path, "");
            $infotemporal_3= pathinfo($arhcivotemporal_3);
            $nombertemporal_3= $infotemporal_3['filename'];
            $info_3 = pathinfo($request->file('guia')->getClientOriginalName());
            $extension_3 = $info_3['extension'];
            $docTempName_3='g_'.$nombertemporal_3.".".$extension_3;
            unlink($arhcivotemporal_3);

            $curso->guia = $docTempName_3;

        }
        if($request->file('presentacion')){
            $archivoAnterior_4=$curso->presentacion;

            $arhcivotemporal_4=tempnam($path, "");
            $infotemporal_4= pathinfo($arhcivotemporal_4);
            $nombertemporal_4= $infotemporal_4['filename'];
            $info_4 = pathinfo($request->file('presentacion')->getClientOriginalName());
            $extension_4 = $info_4['extension'];
            $docTempName_4='p_'.$nombertemporal_4.".".$extension_4;
            unlink($arhcivotemporal_4);

            $curso->presentacion = $docTempName_4;

        }


        if(!$curso->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));

        if($request->file('manual_p')){
            if($archivoAnterior_1){
                $path2 = base_path() .'/public/uploads/documentos/'.$archivoAnterior_1;
                if(file_exists($path2))
                {
                    unlink($path2);
                }
            }
            $request->file('manual_p')->move($path, $docTempName_1);
        }
        if($request->file('manual_f')){
            if($archivoAnterior_2){
                $path2 = base_path() .'/public/uploads/documentos/'.$archivoAnterior_2;
                if(file_exists($path2))
                {
                    unlink($path2);
                }
            }
            $request->file('manual_f')->move($path, $docTempName_2);
        }
        if($request->file('guia')){
            if($archivoAnterior_3){
                $path2 = base_path() .'/public/uploads/documentos/'.$archivoAnterior_3;
                if(file_exists($path2))
                {
                    unlink($path2);
                }
            }
            $request->file('guia')->move($path, $docTempName_3);
        }
        if($request->file('presentacion')){
            if($archivoAnterior_4){
                $path2 = base_path() .'/public/uploads/documentos/'.$archivoAnterior_4;
                if(file_exists($path2))
                {
                    unlink($path2);
                }
            }
            $request->file('presentacion')->move($path, $docTempName_4);
        }


        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));

    }

    public function delete($id)
    {
        $user=Auth::user();

        if ($user->can('delete', Curso::class))
        {
            $curso = Curso::find($id);
            if($curso==null)
            {
                return Redirect::back()
                    ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Area ingresada no encontrada."));

            }


            if($curso->cursoProgramado->count() > 0){
                return Redirect::back()->with('alert',Funciones::getAlert("danger", "Error", "Esta acción de formación no puede ser eliminada, forma parte de cursos programados."));
            }

            if ($curso->delete()) {
                if($curso->manual_p){
                    $path2 = base_path() .'/public/uploads/documentos/'.$curso->manual_p;
                    if(file_exists($path2))
                    {
                        unlink($path2);
                    }
                }
                if($curso->manual_f){
                    $path2 = base_path() .'/public/uploads/documentos/'.$curso->manual_f;
                    if(file_exists($path2))
                    {
                        unlink($path2);
                    }
                }
                if($curso->guia){
                    $path2 = base_path() .'/public/uploads/documentos/'.$curso->guia;
                    if(file_exists($path2))
                    {
                        unlink($path2);
                    }
                }
                if($curso->presentacion){
                    $path2 = base_path() .'/public/uploads/documentos/'.$curso->presentacion;
                    if(file_exists($path2))
                    {
                        unlink($path2);
                    }
                }
                return Redirect::back()
                    ->with('alert',Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));

            }

            return Redirect::back()
                ->with('alert',Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));

        }

        return Redirect::back()
            ->with("alert",Funciones::getAlert("danger", "Error al Intentar Editar", "No tienes permisos para realizar esta accion."));

    }
}
