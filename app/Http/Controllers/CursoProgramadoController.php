<?php

namespace App\Http\Controllers;

use App\Models\CourseStatus;
use App\Models\Scheduled;
use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use App\Models\Funciones;
use App\Models\Participant;
use App\Models\Person;

use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\CursoProgramadoForm;
use App\Models\Facilitator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CursoProgramadoController extends Controller
{
    public function get($id)
    {

        $user = Auth::user();
        $cursop = Scheduled::find($id);
        if(!$cursop || $user->cannot('get', Scheduled::class))
        {
            return json_encode([]);
        }

        return json_encode($cursop);

    }

    public function getAll()
    {

        $user = Auth::user();

        if($user->cannot('getAll', Scheduled::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $titulos = filter_input(INPUT_GET,'titulos',FILTER_SANITIZE_STRING);
        $id_facilitador = filter_input(INPUT_GET,'id_facilitador',FILTER_SANITIZE_NUMBER_INT);
        $id_estado = filter_input(INPUT_GET, 'id_estado',FILTER_SANITIZE_NUMBER_INT);
        $date = filter_input(INPUT_GET,'fechas',FILTER_SANITIZE_NUMBER_INT);

        $fecha = $date;

        $facilitadores = User::where('role_id',4)->with('person')->get();
        
        $participantes4 = Participant::all();
        $pts = $participantes4->pluck('person_id');

        $participantes = User::whereNotIn('person_id', $pts)->where('role_id',5)
        ->get();


        $cursos = Scheduled::orderBy("start_date","desc")->with('course')->with('facilitator')->with('courseStatus');
        $estados = CourseStatus::orderBy('name','asc')->get();
        //dd($cursos->paginate(10));

        if($titulos){
           // $cursos=$cursos->where('curso.titulo','LIKE',"%$titulos%");
           $cursos=$cursos->whereHas('course', function($q) use($titulos){
                    $q->where('title', 'like', '%'.$titulos.'%');});
            
        }
        if($id_facilitador)
           $cursos=$cursos->where('facilitator_id','=',$id_facilitador);
        if($id_estado){
            $cursos= $cursos->where('course_status_id',$id_estado);
        }
        if($date){
            $fecha= new Carbon('01-'.$date);
           $cursos = $cursos->whereMonth('start_date',$fecha->month)
                            ->whereYear('end_date',$fecha->year);
            $fecha=$fecha->format('m-Y');
        }

        
        return view('pages.admin.cursosprogramados.index')
                ->with('cursos',$cursos->paginate(10))
              //  ->with('categorias',$categorias)
                ->with('facilitadores',$facilitadores)
                ->with('participantes',$participantes)
                ->with('titulos',$titulos)
                ->with('id_facilitador',$id_facilitador)
                ->with('fechas',$fecha)->with('estados',$estados)->with('id_estado',$id_estado);
    }

    public function store(CursoProgramadoForm $request)
    {
        //dd($request);
        $user=Auth::user();

        if ($user->can('store', Scheduled::class)){

            $cursoprogramado = new Scheduled();

            $cursoprogramado->course_id = $request->titulo;
            $cursoprogramado->facilitator_id = $request->facilitador;
            $cursoprogramado->start_date = date("Y-m-d", strtotime($request->fecha_i));
            $cursoprogramado->end_date = date("Y-m-d", strtotime($request->fecha_f));

            if (today() < $cursoprogramado->start_date)
                $cursoprogramado->course_status_id=CourseStatus::POR_DICTAR;
            else if (today() <= $cursoprogramado->end_date)
                $cursoprogramado->course_status_id=CourseStatus::EN_CURSO;
            //dd($cursoprogramado);
            
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


        $cursoProg = Scheduled::find($id);


        if (!$cursoProg)
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El curso seleccionado no existe."));

        if ($user->cannot('update',Scheduled::class))
            return Redirect()::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));

            $cursoProg->facilitator_id = $request->facilitador;
            $cursoProg->start_date = date("Y-m-d", strtotime($request->fecha_i));
            $cursoProg->end_date = date("Y-m-d", strtotime($request->fecha_f));

        if(!$cursoProg->save())
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));

        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));

    }

    public function delete($id)
    {
        $user=Auth::user();

        if ($user->can('delete', Scheduled::class))
        {
                $cursoProg = Scheduled::find($id);
                if($cursoProg==null)
               {
                    return Redirect::back()
                        ->with("alert",Funciones::getAlert("danger", "Error al Intentar Eliminar", "Curso no encontrado."));
               }
                if($cursoProg->delete()){
                    $cursoProg->participants()->delete(); //deletes record of users in a course
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
        //auth
        $user = Auth::user();
        if($user->cannot('misCursos', Scheduled::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
        }
        
        
        //filters
        $titulos = filter_input(INPUT_GET,'titulos',FILTER_SANITIZE_STRING);
        $id_facilitador = filter_input(INPUT_GET,'id_facilitador',FILTER_SANITIZE_NUMBER_INT);
        $date = filter_input(INPUT_GET,'fechas',FILTER_SANITIZE_NUMBER_INT);
        $fecha = $date;
        $lista = $categorias = Category::orderBy("name","asc");
        $categorias = $lista->pluck('name','id');
        $facilitadores = User::where('role_id','4')->get();
        $cursos = Scheduled::orderBy("id","asc");
        
        
        if($user->isFacilitador()){
            $cursos = Scheduled::with('facilitator')->with('course')->where('facilitator_id', $user->person->facilitator->id);
        }

        if($user->isParticipante()){
            
            $courses = $user->person->scheduled; //courses is a Collection
            
            foreach ($courses as $course) {
                $values[] = $course->id;
            }
            
            if(count($courses) > 0){
                $cursos = Scheduled::with('facilitator')->with('course')->whereIn('id', $values); //cursos needs to be Builder
                
            }else{
                $cursos = Scheduled::with('facilitator')->with('course')->where('id', null);
            }
        }
        
        //page with filters
        if($titulos){
           $cursos=$cursos->whereHas('course', function($q) use($titulos){
                    $q->where('title', 'like', '%'.$titulos.'%');});
        }
        if($id_facilitador)
           $cursos=$cursos->where('scheduled_course.facilitator_id','=',$id_facilitador);
        if($date){
            $fecha= new Carbon('01-'.$date);
            $cursos = $cursos->whereMonth('scheduled_course.start_date',$fecha->month)
                            ->whereYear('scheduled_course.end_date',$fecha->year);
            $fecha=$fecha->format('m-Y');
        }

        $cursos = $cursos->orderBy("start_date","asc");
        
        return view('pages.admin.usuarios.miscursos')
                ->with('cursos',$cursos->paginate(10)) //$cursos needs to be Builder in order to paginate
                ->with('categorias',$categorias)
                ->with('facilitadores',$facilitadores)
                ->with('titulos',$titulos)
                ->with('id_facilitador',$id_facilitador)
                ->with('fechas',$fecha);
    }//misCursos .miscursos.blade "u/mis_acciones"

    public function userCursos($id)
    {
        $user = Auth::user();

        if($user->cannot('userCursos', Scheduled::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
        }

        $usuario = User::with('person')->find($id);        
        
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

        $lista = $categorias = Category::orderBy("name","asc");
        $categorias = $lista->pluck('name','id');

        $facilitadores = User::with('person')->where('role_id','4')
                    ->get();

        $cursos = Scheduled::orderBy("id","asc");

        //$cursos= $usuario->cursoFacilitador();
        if($usuario->isFacilitador()){
            $cursos =  Scheduled::with('facilitator')->with('course')->where('facilitator_id', $usuario->person->facilitator->id)->get();    
        }else{
            $cursos = [];
        }
        if($usuario->isParticipante()){

            $courses = $usuario->person->participant; //courses is a Collection
            
            foreach ($courses as $course) {
                $values[] = $course->scheduled_id;
            }

            if (count($courses) > 0) {
                $cursos = Scheduled::whereIn('id',$values)->with('facilitator')->with('course');
            }  else {
                $cursos = Scheduled::where('id',null)->with('facilitator')->with('course');
            }  
        }

       if($titulos){
           $cursos=$cursos->whereHas('course', function($q) use($titulos){
                    $q->where('title', 'like', '%'.$titulos.'%');});
        }
        if($id_facilitador){
            
            $cursos = $cursos->where('scheduled_course.facilitator_id', '=', $id_facilitador); 
        }
        if($date){
            $fecha= new Carbon('01-'.$date);

           $cursos = $cursos->whereMonth('scheduled_course.start_date',$fecha->month)
                            ->whereYear('scheduled_course.start_date',$fecha->year);
            $fecha=$fecha->format('m-Y');
        } 
        $cursos = $cursos->orderBy("start_date","asc");
    
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
