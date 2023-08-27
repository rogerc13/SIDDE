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
use App\Models\Prerequisite;

use Illuminate\Http\Request;
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
        

        if($titulos){
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

        
        if($usuario->isFacilitador()){
            $cursos =  Scheduled::with('facilitator')->with('course')->where('facilitator_id', $usuario->person->facilitator->id);    
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

    public function assignList(Request $request){
        $scheduledId = $request->scheduled_id;
        //get dates of selected course
        $scheduledCourse = Scheduled::with('course')->find($scheduledId);
        $courseMaxCapacity = $scheduledCourse->course->capacity[0]->max;

        //check current amount of participants
        $participantAmount = Participant::where('scheduled_id',$scheduledId)->count();

        //get ID of selected course
        //check if course has prerequisite on the prerequiste table
        //if it does 
        //get the prerequisite ID
        //get the person that has the prerequisite ID on the table participants and participant_status_id 3 //Aprobado
        //if it doesn't
        //get all the persons, do nothing

        //get prerequisite of selected course
        $coursePrerequisite = Prerequisite::where('course_id', $scheduledCourse->course->id)->select('prerequisite_id')->get();

        //return json_encode(count($coursePrerequisite));

                

        //return json_encode($coursePrerequisite[0]->prerequisite_id);

        //return json_encode(['prerequisite' => $coursePrerequisite[0]->prerequisite_id]);

        //get person in participant table with scheduled_id that matches the course id prerequisite AND has participant status 3 
        /* $participantTest = Participant::with('scheduled','person')->whereHas('scheduled',function($query) use($coursePrerequisite){
            $query->where('course_id',$coursePrerequisite[0]->prerequisite_id);
        })->where('participant_status_id',3)->get(); */

        /* $participantTest = Person::with('scheduled','participant','user')->whereNotIn('id',$participants)->whereHas('user',function($query){
            $query->where('role_id',5);
        })->whereHas('scheduled',function($query) use($coursePrerequisite){
            $query->where('course_id',$coursePrerequisite[0]->prerequisite_id);
        })->whereHas('participant',function($query){
            $query->where('participant_status_id',3);
        })->get();

        return json_encode(['participantWithPrequisite' => $participantTest]); */

    

        if($participantAmount == $courseMaxCapacity){
            $response = ['success' => false, 'message'=>'Capacidad Máxima de Participantes Alcanzada.'];
            return response()->json($response);
        }else{
            $participants = Person::with('scheduled')->whereHas(
                'scheduled',
                function ($query) use ($scheduledCourse) {
                    $query->whereBetween('start_date', array($scheduledCourse->start_date, $scheduledCourse->end_date));
                }
            )->get()->pluck('id');

            //get participants not in the list
            //if course has no prerequisite
            if(count($coursePrerequisite) === 0){
                $people = Person::whereNotIn('id', $participants)->with('user')->whereHas(
                    'user',
                    function ($query) {
                        $query->where('role_id', 5);
                    }
                )->get();
            }else{
                $people = Person::with('scheduled', 'participant', 'user')->whereNotIn('id', $participants)->whereHas('user', function ($query) {
                    $query->where('role_id', 5);
                })->whereHas('scheduled', function ($query) use ($coursePrerequisite) {
                    $query->where('course_id', $coursePrerequisite[0]->prerequisite_id);
                })->whereHas('participant', function ($query) {
                    $query->where('participant_status_id', 3);
                })->get();
            }
            
            $response = ['success' => true, 'message'=> '','list'=>$people];
            return json_encode($response);
        }
        
    }

    public function cancel($id) //cancel af programadas / courses
    {
        $user = Auth::user();

        if ($user->can('cancel', Scheduled::class)) {
            $cursoProg = Scheduled::find($id);
            if ($cursoProg == null) {
                return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger", "Error al Intentar Cancelar", "Curso no encontrado."));
            }

            $cursoProg->course_status_id = 4;

            if ($cursoProg->save()) {

                //$cursoProg->participants()->participant_status_id = 4; //set status of participamts in a course as cancelled
                $cursoProg->participants()->update(['participant_status_id' => 4]);
            
                return Redirect::back()
                    ->with("alert", Funciones::getAlert("success", "Cancelado Exitosamente", "Operacón Exitosa."));
            }

            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Cancelar", "Operación Erronea."));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al Intentar Cancelar", "No tienes permisos para realizar esta acción."));
    }//end cancel af programadas / courses

}
