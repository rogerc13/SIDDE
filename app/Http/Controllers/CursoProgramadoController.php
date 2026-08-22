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
use App\Models\CourseSession;
use App\Models\Location;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\CourseScheduleForm;
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
        

        $cursos = Scheduled::orderBy("start_date","desc")->with(['course','facilitator','courseStatus','sessions']);
        $estados = CourseStatus::orderBy('name','asc')->get();
        $categoriasAcciones = Category::with('courses')->get();
        $locations = Location::with('floor.building')->orderBy('name', 'asc')->get();
        

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
                ->with('fechas',$fecha)->with('estados',$estados)->with('id_estado',$id_estado)
                ->with('categoriasAcciones',$categoriasAcciones)->with('locations',$locations);
    }

    private function getLocationsForScheduling()
    {
        $from = Carbon::today()->subMonths(3)->toDateString();
        $to = Carbon::today()->addMonths(3)->toDateString();

        return Location::with('floor.building')
            ->withCount(['sessions' => function ($q) use ($from, $to) {
                $q->whereBetween('session_date', [$from, $to]);
            }])
            ->orderByDesc('sessions_count')
            ->orderBy('name', 'asc')
            ->get();
    }

    public function createSchedule()
    {
        $user = Auth::user();

        if ($user->cannot('store', Scheduled::class)) {
            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $facilitadores = User::where('role_id', 4)->with('person')->get();
        $categoriasAcciones = Category::with('courses')->get();
        $locations = $this->getLocationsForScheduling();

        return view('pages.admin.cursosprogramados.schedule')
            ->with('mode', 'create')
            ->with('scheduledId', null)
            ->with('selectedCourseId', null)
            ->with('selectedFacilitatorId', null)
            ->with('courseDuration', 0)
            ->with('existingSessions', [])
            ->with('facilitadores', $facilitadores)
            ->with('categoriasAcciones', $categoriasAcciones)
            ->with('locations', $locations);
    }

    public function editSchedule($id)
    {
        $user = Auth::user();

        if ($user->cannot('update', Scheduled::class)) {
            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $scheduled = Scheduled::with(['course', 'facilitator', 'sessions.location' => function ($q) {
            $q->withTrashed();
        }])->find($id);

        if (!$scheduled) {
            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Editar", "El curso seleccionado no existe."));
        }

        $facilitadores = User::where('role_id', 4)->with('person')->get();
        $categoriasAcciones = Category::with('courses')->get();
        $locations = $this->getLocationsForScheduling();

        $existingSessions = $scheduled->sessions->map(function ($s) {
            return [
                'id' => $s->id,
                'location_id' => $s->location_id,
                'location_name' => $s->location ? $s->location->name : '',
                'session_date' => $s->session_date,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'duration_hours' => $s->durationHours(),
                'notes' => $s->notes,
            ];
        })->values()->all();

        return view('pages.admin.cursosprogramados.schedule')
            ->with('mode', 'edit')
            ->with('scheduledId', $scheduled->id)
            ->with('selectedCourseId', $scheduled->course_id)
            ->with('selectedFacilitatorId', $scheduled->facilitator_id)
            ->with('courseDuration', $scheduled->course->duration)
            ->with('statusName', $scheduled->courseStatus->name)
            ->with('statusBadge', $scheduled->badgeStatus())
            ->with('existingSessions', $existingSessions)
            ->with('facilitadores', $facilitadores)
            ->with('categoriasAcciones', $categoriasAcciones)
            ->with('locations', $locations);
    }

    public function viewSchedule($id)
    {
        $user = Auth::user();

        if ($user->cannot('get', Scheduled::class)) {
            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $scheduled = Scheduled::with(['course.capacity', 'facilitator.person', 'sessions.location' => function ($q) {
            $q->withTrashed();
        }])->find($id);

        if (!$scheduled) {
            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Ver", "El curso seleccionado no existe."));
        }

        $existingSessions = $scheduled->sessions->map(function ($s) {
            return [
                'id' => $s->id,
                'location_id' => $s->location_id,
                'location_name' => $s->location ? $s->location->name : '',
                'session_date' => $s->session_date,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'duration_hours' => $s->durationHours(),
                'notes' => $s->notes,
            ];
        })->values()->all();

        $capacity = $scheduled->course->capacity->first();

        return view('pages.admin.cursosprogramados.details')
            ->with('scheduled', $scheduled)
            ->with('existingSessions', $existingSessions)
            ->with('totalHours', collect($existingSessions)->sum('duration_hours'))
            ->with('capacityMax', $capacity ? $capacity->max : null)
            ->with('participantsCount', $scheduled->participants()->count());
    }

    public function updateSchedule(CourseScheduleForm $request, $id)
    {
        $user = Auth::user();

        if ($user->cannot('update', Scheduled::class)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.'], 403);
            }

            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Editar", "No tienes permisos para realizar esta acción."));
        }

        $scheduled = Scheduled::find($id);

        if (!$scheduled) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'El curso seleccionado no existe.'], 404);
            }

            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Editar", "El curso seleccionado no existe."));
        }

        $course = Course::findOrFail($request->titulo);

        $dates = collect($request->sessions);
        $scheduled->facilitator_id = $request->facilitador;
        $scheduled->start_date = $dates->min('session_date');
        $scheduled->end_date = $dates->max('session_date');
        $scheduled->save();

        $incomingIds = collect($request->sessions)->pluck('id')->filter()->values()->all();

        if (empty($incomingIds)) {
            $scheduled->sessions()->delete();
        } else {
            $scheduled->sessions()->whereNotIn('id', $incomingIds)->delete();
        }

        foreach ($request->sessions as $session) {
            $attributes = [
                'location_id' => $session['location_id'],
                'session_date' => $session['session_date'],
                'start_time' => $session['start_time'],
                'end_time' => $session['end_time'],
                'notes' => $session['notes'] ?? null,
            ];

            if (!empty($session['id'])) {
                $existing = CourseSession::find($session['id']);

                if ($existing && $existing->scheduled_course_id == $scheduled->id) {
                    $existing->update($attributes);
                    continue;
                }
            }

            $attributes['scheduled_course_id'] = $scheduled->id;
            CourseSession::create($attributes);
        }

        if ($request->expectsJson()) {
            session()->flash("alert", Funciones::getAlert("success", "Curso Actualizado Exitosamente", "Operación Exitosa."));

            return response()->json(['success' => true]);
        }

        return Redirect::to(url('u/af_programadas'))
            ->with("alert", Funciones::getAlert("success", "Curso Actualizado Exitosamente", "Operación Exitosa."));
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
            $cursos = Scheduled::with(['facilitator','course.file'])->where('facilitator_id', $user->person->facilitator->id);
        }

        if($user->isParticipante()){
            
            $courses = $user->person->scheduled; //courses is a Collection
            
            foreach ($courses as $course) {
                $values[] = $course->id;
            }
            
            if(count($courses) > 0){
                $cursos = Scheduled::with(['facilitator','course'])->whereIn('id', $values); //cursos needs to be Builder
                
            }else{
                $cursos = Scheduled::with(['facilitator','course'])->where('id', null);
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
            $cursos =  Scheduled::with(['facilitator','course'])->where('facilitator_id', $usuario->person->facilitator->id);    
        }else{
            $cursos = [];
        }
        if($usuario->isParticipante()){

            $courses = $usuario->person->participant; //courses is a Collection
            
            foreach ($courses as $course) {
                $values[] = $course->scheduled_id;
            }

            if (count($courses) > 0) {
                $cursos = Scheduled::with(['facilitator','course'])->whereIn('id',$values);
            }  else {
                $cursos = Scheduled::with(['facilitator','course'])->where('id',null);
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
        
        $scheduledCourse = Scheduled::with('course')->find($scheduledId);

        if (!$scheduledCourse->isPorDictar()) {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden asignar participantes a una acción de formación que no esté por dictar.'
            ]);
        }

        $courseMaxCapacity = $scheduledCourse->course->capacity[0]->max;

        //check current amount of participants
        $participantAmount = Participant::where('scheduled_id',$scheduledId)->count();
        
        //get prerequisite of selected course
        $coursePrerequisite = Prerequisite::where('course_id', $scheduledCourse->course->id)->select('prerequisite')->get();
        
        if($participantAmount == $courseMaxCapacity){ //check if course is at max capacity
            $response = ['success' => false, 'message'=>'Capacidad Máxima de Participantes Alcanzada.'];
            return response()->json($response);
        }else{
            
            if((count($coursePrerequisite) === 0) || $coursePrerequisite[0]->prerequisite === null){ //if course has no prerequisite
                                
                //get scheduled id's of scheduled courses that overlap with selected course and without status
                $scheduledOverlap = Scheduled::whereBetween('start_date', array($scheduledCourse->start_date, $scheduledCourse->end_date)
                )->where('course_status_id','!=','4')->get()->pluck('id');
                
                //get all participants on participants' table that match the scheduled course id and status is different of canceled '4'
                $participantsOverlap = Participant::whereIn('scheduled_id',$scheduledOverlap)
                /* ->where('participant_status_id','!=' ,4) */
                ->get()->pluck('person_id');

                //get list of all participants
                $participantList = Person::whereHas('user', function($query){
                    $query->where('role_id','5');
                })->get()->pluck('id');

                //getparticipants without those who are in a course that overlap
                $availableParticipants = $participantList->diff($participantsOverlap);

                $people = Person::whereIn('id',$availableParticipants)->get();

                $response = ['success' => true, 'message'=> 'No Prerequisite, List of Available Participants','list'=>$people];
                return json_encode($response);

            }else{ //if course has a prerequisite

                //get list of courses that met the prerequsite
                $courseList = Scheduled::whereHas('Course',function($query) use($coursePrerequisite){
                    $query->where('code',$coursePrerequisite[0]->prerequisite)->where('course_status_id','3');
                })->get()->pluck('id');
                
                //get scheduled id's of scheduled courses that overlap with selected course and without status
                $scheduledOverlap = Scheduled::whereBetween('start_date', array($scheduledCourse->start_date, $scheduledCourse->end_date)
                )->where('course_status_id','!=','4')->get()->pluck('id');
                
                //get all participants on participants' table that match the scheduled course id and status is different of canceled '4'
                $participantsOverlap = Participant::whereIn('scheduled_id',$scheduledOverlap)
                /* ->where('participant_status_id','!=' ,4) */
                ->get()->pluck('person_id');

                //get all participants that met the prerequisite
                $participantsWithPrerequisite = Participant::with('Person')->whereIn('scheduled_id',$courseList)
                ->where('participant_status_id','3')->whereNotIn('id',Participant::where('scheduled_id','25')->select('id')->get())
                ->get()->pluck('person_id');

                $availableParticipants = $participantsWithPrerequisite->diff($participantsOverlap);
                
                $people = Person::whereIn('id',$availableParticipants)->get();

                $response = ['success' => true, 'message'=> 'List of Available Participants','list'=>$people];
                return json_encode($response);

            }
        }//end max capacity check  
    }//end assignList()

    public function cancel($id) //cancel af programadas / courses
    {
        $user = Auth::user();
        //dd($id);

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

    public function schedule(CourseScheduleForm $request)
    {
        $user = Auth::user();

        if ($user->cannot('store', Scheduled::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $course = Course::find($request->titulo);

        // Create scheduled course
        $scheduled = new Scheduled();
        $scheduled->course_id = $request->titulo;
        $scheduled->facilitator_id = $request->facilitador;
        $dates = collect($request->sessions);
        $scheduled->start_date = $dates->min('session_date');
        $scheduled->end_date = $dates->max('session_date');

        $today = Carbon::today();
        $start = Carbon::parse($scheduled->start_date);
        $end = Carbon::parse($scheduled->end_date);

        if ($today < $start)
            $scheduled->course_status_id = CourseStatus::POR_DICTAR;
        else if ($today <= $end)
            $scheduled->course_status_id = CourseStatus::EN_CURSO;
        else
            $scheduled->course_status_id = CourseStatus::CULMINADO;

        if ($scheduled->save()) {
            // Create sessions
            foreach ($request->sessions as $session) {
                CourseSession::create([
                    'scheduled_course_id' => $scheduled->id,
                    'location_id' => $session['location_id'],
                    'session_date' => $session['session_date'],
                    'start_time' => $session['start_time'],
                    'end_time' => $session['end_time'],
                    'notes' => $session['notes'] ?? null,
                ]);
            }

            if ($request->expectsJson()) {
                session()->flash("alert", Funciones::getAlert("success", "Curso Programado Exitosamente", "Operación Exitosa."));

                return response()->json(['success' => true]);
            }

            return Redirect::back()
                ->with("alert", Funciones::getAlert("success", "Curso Programado Exitosamente", "Operación Exitosa."));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al Intentar Programar Curso", "Operación Errónea."));
    }

    public function getBlockedSlots(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        $facilitatorId = $request->input('facilitator_id');
        $excludeId = $request->input('exclude_scheduled_id');

        $sessions = CourseSession::with(['location' => function ($q) {
            $q->withTrashed();
        }])
            ->whereBetween('session_date', [$start, $end])
            ->whereNull('deleted_at');

        if ($excludeId) {
            $sessions->where('scheduled_course_id', '!=', $excludeId);
        }

        $sessions = $sessions->get();

        $locationBlocks = $sessions->filter(function ($s) {
            return $s->location !== null;
        })->map(function ($s) {
            return [
                'id' => 'blocked-loc-' . $s->id,
                'title' => ($s->location->name ?? 'Ubicación') . ' (Ocupado)',
                'start' => $s->session_date . 'T' . $s->start_time,
                'end' => $s->session_date . 'T' . $s->end_time,
                'color' => '#999',
                'location_id' => $s->location_id,
                'type' => 'location',
                'rendering' => 'background',
            ];
        });

        $facilitatorBlocks = collect();
        if ($facilitatorId) {
            $facilitatorSessions = $sessions->filter(function ($s) use ($facilitatorId) {
                return $s->scheduled && $s->scheduled->facilitator_id == $facilitatorId;
            });

            $facilitator = Facilitator::with('person')->find($facilitatorId);
            $facilitatorName = $facilitator && $facilitator->person
                ? $facilitator->person->name . ' ' . $facilitator->person->last_name
                : 'Facilitador';

            $facilitatorBlocks = $facilitatorSessions->map(function ($s) use ($facilitatorName) {
                return [
                    'id' => 'blocked-fac-' . $s->id,
                    'title' => $facilitatorName . ' (Facilitador - Ocupado)',
                    'start' => $s->session_date . 'T' . $s->start_time,
                    'end' => $s->session_date . 'T' . $s->end_time,
                    'color' => '#e67e22',
                    'location_id' => $s->location_id,
                    'facilitator_id' => $s->scheduled->facilitator_id,
                    'type' => 'facilitator',
                    'rendering' => 'background',
                ];
            });
        }

        return response()->json($locationBlocks->merge($facilitatorBlocks)->values());
    }

}
