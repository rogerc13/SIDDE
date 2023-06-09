<?php

namespace App\Http\Controllers;

use App\Models\CPStatus;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\CursoProgramado;
use App\Models\Curso;
use App\Models\Categoria;
use App\Models\User;
use App\Models\Funciones;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\CursoProgramadoForm;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CursoProgramadoController extends Controller
{
    public function get($id)
    {
        // $user = Auth::user();

        // $curso = Curso::first();
        // $cat = Curso::categoriasCursos();
        // dd($cat[0]->cursos->count());
        // $user = User::find('3');
        // dd($user->misCursos[0]->curso);

        $user = Auth::user();
        $cursop = CursoProgramado::find($id);
        if(!$cursop || $user->cannot('get', CursoProgramado::class))
        {
            return json_encode([]);
        }

        return json_encode($cursop);

    }

    public function getAll()
    {


        $user = Auth::user();

        if($user->cannot('getAll', CursoProgramado::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $titulos = filter_input(INPUT_GET,'titulos',FILTER_SANITIZE_STRING);
        $id_facilitador = filter_input(INPUT_GET,'id_facilitador',FILTER_SANITIZE_NUMBER_INT);
        $date = filter_input(INPUT_GET,'fechas',FILTER_SANITIZE_NUMBER_INT);

        $fecha = $date;


       // $lista = $categorias = Categoria::orderBy("nombre","asc");
       // $categorias = $lista->pluck('nombre','id');


        $facilitadores = User::where('rol_id','4')
                    ->orderBy("nombre","asc")
                    ->get();

        $participantes = User::where('rol_id','5')
                    ->orderBy("nombre","asc")
                    ->get();


        $cursos = CursoProgramado::orderBy("fecha_i","desc")->with('curso')->with('facilitador')->with('cpStatus');

        if($titulos){
           // $cursos=$cursos->where('curso.titulo','LIKE',"%$titulos%");
           $cursos=$cursos->whereHas('curso', function($q) use($titulos){
                    $q->where('titulo', 'like', '%'.$titulos.'%');});
        }
        if($id_facilitador)
           $cursos=$cursos->where('user_id','=',$id_facilitador);
        if($date){
            $fecha= new Carbon('01-'.$date);
           $cursos = $cursos->whereMonth('fecha_i',$fecha->month)
                            ->whereYear('fecha_i',$fecha->year);
            $fecha=$fecha->format('m-Y');
        }


        return view('pages.admin.cursosprogramados.index')
                ->with('cursos',$cursos->paginate(10))
              //  ->with('categorias',$categorias)
                ->with('facilitadores',$facilitadores)
                ->with('participantes',$participantes)
                ->with('titulos',$titulos)
                ->with('id_facilitador',$id_facilitador)
                ->with('fechas',$fecha);
    }

    public function store(CursoProgramadoForm $request)
    {
        $user=Auth::user();

        if ($user->can('store', CursoProgramado::class)){


            $cursoprogramado = new CursoProgramado();
            $cursoprogramado->curso_id = $request->titulo;
            $cursoprogramado->user_id = $request->facilitador;
            $cursoprogramado->fecha_i = date("Y-m-d", strtotime($request->fecha_i));
            $cursoprogramado->fecha_f = date("Y-m-d", strtotime($request->fecha_f));

            if (today()<$cursoprogramado->fecha_i)
                $cursoprogramado->status_id=CPStatus::POR_DICTAR;
            else if (today()<=$cursoprogramado->fecha_f)
                $cursoprogramado->status_id=CPStatus::EN_CURSO;

            if($cursoprogramado->save()){
                return Redirect::back()
                        ->with("alert",Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));

            }

            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar programar Curso", "Operacion Erronea."));

        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));

    }



    public function count()
    {
        //
    }

    public function update(CursoProgramadoForm $request, $id)
    {
        $user=Auth::user();


        $cursoProg = CursoProgramado::find($id);


        if (!$cursoProg)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El curso seleccionado no existe."));

        if ($user->cannot('update',CursoProgramado::class))
            return Redirect()::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));

            $cursoProg->user_id = $request->facilitador;
            $cursoProg->fecha_i = date("Y-m-d", strtotime($request->fecha_i));
            $cursoProg->fecha_f = date("Y-m-d", strtotime($request->fecha_f));

        if(!$cursoProg->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));

        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));

    }

    public function delete($id)
    {
        $user=Auth::user();

        if ($user->can('delete', CursoProgramado::class))
        {
                $cursoProg = CursoProgramado::find($id);
                if($cursoProg==null)
               {
                    return Redirect::back()
                        ->with("alert",Funciones::getAlert("danger", "Error al Intentar Eliminar", "Curso no encontrado."));
               }
                if($cursoProg->delete()){
                    $cursoProg->participantesCurso()->delete(); //deletes record of users in a course
                    return Redirect::back()
                            ->with("alert",Funciones::getAlert("success", "Elimiando Exitosamente", "Operacion Exitosa."));
                }

                return Redirect::back()
                        ->with("alert",Funciones::getAlert("danger", "Error al Intentar Eliminar", "Operacion Erronea."));

        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Eliminar", "No tienes permisos para realizar esta accion."));
    }

    public function misCursos()
    {

        $user = Auth::user();

        if($user->cannot('misCursos', CursoProgramado::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $titulos = filter_input(INPUT_GET,'titulos',FILTER_SANITIZE_STRING);
        $id_facilitador = filter_input(INPUT_GET,'id_facilitador',FILTER_SANITIZE_NUMBER_INT);
        $date = filter_input(INPUT_GET,'fechas',FILTER_SANITIZE_NUMBER_INT);


        $fecha = $date;

        $lista = $categorias = Categoria::orderBy("nombre","asc");
        $categorias = $lista->pluck('nombre','id');

        $facilitadores = User::where('rol_id','4')
                    ->orderBy("nombre","asc")
                    ->get();

        $cursos = CursoProgramado::orderBy("id","asc");

        $cursos= $user->cursoFacilitador();

        if($user->isParticipante())
            $cursos= $user->misCursos();

        if($titulos){

           // $cursos=$cursos->where('curso.titulo','LIKE',"%$titulos%");
           $cursos=$cursos->whereHas('curso', function($q) use($titulos){
                    $q->where('titulo', 'like', '%'.$titulos.'%');});
        }
        if($id_facilitador)
           $cursos=$cursos->where('curso_programado.user_id','=',$id_facilitador);
        if($date){
            $fecha= new Carbon('01-'.$date);

           $cursos = $cursos->whereMonth('curso_programado.fecha_i',$fecha->month)
                            ->whereYear('curso_programado.fecha_i',$fecha->year);
            $fecha=$fecha->format('m-Y');
        }

        $cursos = $cursos->orderBy("fecha_i","asc");

        return view('pages.admin.usuarios.miscursos')
                ->with('cursos',$cursos->paginate(10))
                ->with('categorias',$categorias)
                ->with('facilitadores',$facilitadores)
                ->with('titulos',$titulos)
                ->with('id_facilitador',$id_facilitador)
                ->with('fechas',$fecha);
    }

    public function userCursos($id)
    {

        $user = Auth::user();

        if($user->cannot('userCursos', CursoProgramado::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }


        $usuario = User::find($id);

        if (!$usuario){
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "El usuario seleccionado no existe."));
        }

        if(!($usuario->isParticipante() || $usuario->isFacilitador())){
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error", "El usuario seleccionado invalido."));
        }

        $titulos = filter_input(INPUT_GET,'titulos',FILTER_SANITIZE_STRING);
        $id_facilitador = filter_input(INPUT_GET,'id_facilitador',FILTER_SANITIZE_NUMBER_INT);
        $date = filter_input(INPUT_GET,'fechas',FILTER_SANITIZE_NUMBER_INT);


        $fecha = $date;

        $lista = $categorias = Categoria::orderBy("nombre","asc");
        $categorias = $lista->pluck('nombre','id');

        $facilitadores = User::where('rol_id','4')
                    ->orderBy("nombre","asc")
                    ->get();

        $cursos = CursoProgramado::orderBy("id","asc");

        $cursos= $usuario->cursoFacilitador();

        if($usuario->isParticipante())
            $cursos= $usuario->misCursos();

        if($titulos){

           // $cursos=$cursos->where('curso.titulo','LIKE',"%$titulos%");
           $cursos=$cursos->whereHas('curso', function($q) use($titulos){
                    $q->where('titulo', 'like', '%'.$titulos.'%');});
        }

        if($id_facilitador)
           $cursos=$cursos->where('curso_programado.user_id','=',$id_facilitador);
        if($date){
            $fecha= new Carbon('01-'.$date);

           $cursos = $cursos->whereMonth('curso_programado.fecha_i',$fecha->month)
                            ->whereYear('curso_programado.fecha_i',$fecha->year);
            $fecha=$fecha->format('m-Y');
        }

        $cursos = $cursos->orderBy("fecha_i","asc");

        return view('pages.admin.usuarios.usercursos')
                ->with('cursos',$cursos->paginate(10))
                ->with('categorias',$categorias)
                ->with('facilitadores',$facilitadores)
                ->with('titulos',$titulos)
                ->with('id_facilitador',$id_facilitador)
                ->with('fechas',$fecha)
                ->with('usuario',$usuario);
    }


}
