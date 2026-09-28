<?php

namespace App\Http\Controllers;

use App\Models\CourseStatus;
use App\Models\Scheduled;
use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use App\Models\Role;
use App\Models\Funciones;
use App\Models\IdType;
use App\Models\Participant;
use App\Models\ParticipantStatus;
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
            return response()->json([], 404);
        }

        return response()->json($cursop);

    }

    public function getAll(Request $request)
    {

        $user = Auth::user();

        if($user->cannot('getAll', Scheduled::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));

        }

        $titulos = $request->input('titulos');
        $id_facilitador = $request->integer('id_facilitador');
        $id_estado = $request->integer('id_estado');
        $date = $request->integer('fechas');

        $fecha = $date;

        $facilitadores = User::where('role_id',Role::FACILITADOR)->with('person')->get();
        
        $pts = Participant::select('person_id')->pluck('person_id');

        $participantes = User::whereNotIn('person_id', $pts)->where('role_id',Role::PARTICIPANTE)
        ->get();
        

        $cursos = Scheduled::orderBy("start_date","desc")->with(['course.capacity','course.category','facilitator','courseStatus','sessions']);
        $estados = CourseStatus::orderBy('name','asc')->get();
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
                ->with('locations',$locations);
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

        $facilitadores = User::where('role_id', Role::FACILITADOR)->with('person')->get();
        $locations = $this->getLocationsForScheduling();

        return view('pages.admin.cursosprogramados.schedule')
            ->with('mode', 'create')
            ->with('scheduledId', null)
            ->with('selectedCourseId', null)
            ->with('selectedCourseTitle', null)
            ->with('selectedFacilitatorId', null)
            ->with('courseDuration', 0)
            ->with('existingSessions', [])
            ->with('facilitadores', $facilitadores)
            ->with('locations', $locations);
    }

    public function editSchedule($id)
    {
        $user = Auth::user();

        if ($user->cannot('update', Scheduled::class)) {
            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $scheduled = Scheduled::with(['course.capacity', 'facilitator', 'sessions.location' => function ($q) {
            $q->withTrashed();
        }])->find($id);

        if (!$scheduled) {
            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Editar", "El curso seleccionado no existe."));
        }

        $facilitadores = User::where('role_id', Role::FACILITADOR)->with('person')->get();
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
            ->with('selectedCourseTitle', $scheduled->course->title)
            ->with('locations', $locations);
    }

    public function viewSchedule($id)
    {
        $user = Auth::user();

        $scheduled = Scheduled::with([
            'course.capacity',
            'course.category',
            'course.modality',
            'course.content',
            'course.prerequisite.prerequisite',
            'course.file',
            'facilitator.person',
            'sessions.location' => function ($q) {
                $q->withTrashed();
            },
        ])->find($id);

        $fallback = ($user->isFacilitador() || $user->isParticipante()) ? url('u/mis_acciones') : url('u/af_programadas');

        if (!$scheduled) {
            return Redirect::to($fallback)
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Ver", "El curso seleccionado no existe."));
        }

        if ($user->cannot('get', $scheduled)) {
            return Redirect::to($fallback)
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
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
            ];
        })->values()->all();

        $capacity = $scheduled->course->capacity->first();

        $isParticipant = $user->isParticipante();

        $participantes = collect();
        $statuses = collect();
        $types = collect();
        $canEvaluate = false;

        if (!$isParticipant) {
            $participantes = Participant::with(['person', 'participantStatus'])
                ->where('scheduled_id', $scheduled->id)
                ->get();

            $statuses = ParticipantStatus::orderBy('id')->get();
            $types = IdType::all();
            $canEvaluate = $user->can('evaluate', $scheduled)
                && ($scheduled->isEnCurso() || $scheduled->isCulminado());
        }

        return view('pages.admin.cursosprogramados.details')
            ->with('scheduled', $scheduled)
            ->with('existingSessions', $existingSessions)
            ->with('totalHours', collect($existingSessions)->sum('duration_hours'))
            ->with('capacityMax', $capacity ? $capacity->max : null)
            ->with('participantsCount', $scheduled->participants()->count())
            ->with('participantes', $participantes)
            ->with('statuses', $statuses)
            ->with('types', $types)
            ->with('canEvaluate', $canEvaluate);
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

        if ($scheduled->isCancelado() || $scheduled->isCulminado()) {
            $message = 'No se puede editar una acción de formación cancelada o culminada.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return Redirect::to(url('u/af_programadas'))
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Editar", $message));
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
            session()->flash("alert", Funciones::getAlert("success", "Acción de formación actualizada exitosamente", "Operación Exitosa."));

            return response()->json(['success' => true]);
        }

        return Redirect::to(url('u/af_programadas'))
            ->with("alert", Funciones::getAlert("success", "Acción de formación actualizada exitosamente", "Operación Exitosa."));
    }

    public function delete($id)
    {
        $user=Auth::user();

        $cursoProg = Scheduled::find($id);

        if($cursoProg==null)
        {
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Eliminar", "Curso no encontrado."));
        }

        if ($user->cannot('delete', $cursoProg))
        {
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Eliminar", "No tienes permisos para realizar esta accion."));
        }

        if (! $cursoProg->canBeDeleted()) {
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "No se puede eliminar", "No se puede eliminar una acción de formación que ya inició o culminó, ya que afectaría los datos relacionados."));
        }

        if($cursoProg->delete()){
            $cursoProg->sessions()->delete();
            $cursoProg->participants()->delete(); //deletes record of users in a course
            return Redirect::back()
                    ->with("alert",Funciones::getAlert("success", "Eliminado Exitosamente", "Operacion Exitosa."));
        }

        return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar Eliminar", "Operacion Erronea."));
    }

    public function misCursos(Request $request)
    {
        //auth
        $user = Auth::user();
        if($user->cannot('misCursos', Scheduled::class))
        {
            return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger","Error al Intentar Acceder","No tienes permisos para realizar esta acción."));
        }
        
        
        //filters
        $titulos = $request->input('titulos');
        $id_facilitador = $request->integer('id_facilitador');
        $date = $request->integer('fechas');
        $fecha = $date;
        $lista = $categorias = Category::orderBy("name","asc");
        $categorias = $lista->pluck('name','id');
        $facilitadores = User::where('role_id',Role::FACILITADOR)->get();
        $cursos = Scheduled::orderBy("id","asc");
        
        
        if($user->isFacilitador()){
            $user->load('person.facilitator');
            $cursos = Scheduled::with(['facilitator','course.file','courseStatus'])->where('facilitator_id', $user->person->facilitator->id);
        }

        if($user->isParticipante()){
            
            $user->load('person.scheduled');
            $courses = $user->person->scheduled; //courses is a Collection
            
            $values = $courses->pluck('id');
            
            if(count($courses) > 0){
                $cursos = Scheduled::with(['facilitator','course','courseStatus'])->whereIn('id', $values); //cursos needs to be Builder
                
            }else{
                $cursos = Scheduled::with(['facilitator','course','courseStatus'])->where('id', null);
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

        $cursos = $cursos->orderBy("start_date","desc");
        
        return view('pages.admin.usuarios.miscursos')
                ->with('cursos',$cursos->paginate(10)) //$cursos needs to be Builder in order to paginate
                ->with('categorias',$categorias)
                ->with('facilitadores',$facilitadores)
                ->with('titulos',$titulos)
                ->with('id_facilitador',$id_facilitador)
                ->with('fechas',$fecha);
    }//misCursos .miscursos.blade "u/mis_acciones"

    public function userCursos(Request $request, $id)
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

        $titulos = $request->input('titulos');
        $id_facilitador = $request->integer('id_facilitador');
        $date = $request->integer('fechas');


        $fecha = $date;

        $lista = $categorias = Category::orderBy("name","asc");
        $categorias = $lista->pluck('name','id');

        $facilitadores = User::with('person')->where('role_id',Role::FACILITADOR)
                    ->get();

        $cursos = Scheduled::orderBy("id","asc");

        
        if($usuario->isFacilitador()){
            $usuario->load('person.facilitator');
            $cursos =  Scheduled::with(['facilitator','course','courseStatus'])->where('facilitator_id', $usuario->person->facilitator->id);    
        }else{
            $cursos = [];
        }
        if($usuario->isParticipante()){

            $usuario->load('person.participant');
            $courses = $usuario->person->participant; //courses is a Collection
            
            $values = $courses->pluck('scheduled_id');

            if (count($courses) > 0) {
                $cursos = Scheduled::with(['facilitator','course','courseStatus'])->whereIn('id',$values);
            }  else {
                $cursos = Scheduled::with(['facilitator','course','courseStatus'])->where('id',null);
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
        $cursos = $cursos->orderBy("start_date","desc");
    
        return view('pages.admin.usuarios.usercursos')
                ->with('cursos',$cursos->paginate(10))
                ->with('categorias',$categorias)
                ->with('facilitadores',$facilitadores)
                ->with('titulos',$titulos)
                ->with('id_facilitador',$id_facilitador)
                ->with('fechas',$fecha)
                ->with('usuario',$usuario);
    }

    public function assignList(Request $request)
    {
        $user = Auth::user();

        if ($user->cannot('store', Participant::class)) {
            return response()->json(['results' => [], 'more' => false], 403);
        }

        $validated = $request->validate([
            'scheduled_id' => ['required', 'integer', 'exists:scheduled_course,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $scheduledCourse = Scheduled::with(['course.capacity'])
            ->findOrFail($validated['scheduled_id']);

        if (!$scheduledCourse->isPorDictar()) {
            return response()->json([
                'results' => [],
                'more' => false,
                'message' => 'No se pueden asignar participantes a una acción de formación que no esté por dictar.',
            ]);
        }

        $courseMaxCapacity = (int) ($scheduledCourse->course->capacity->first()?->max ?? 0);
        $participantAmount = Participant::query()
            ->where('scheduled_id', $scheduledCourse->id)
            ->count();

        if ($courseMaxCapacity === 0 || $participantAmount >= $courseMaxCapacity) {
            return response()->json([
                'results' => [],
                'more' => false,
                'message' => 'Capacidad Máxima de Participantes Alcanzada.',
            ]);
        }

        $coursePrerequisite = Prerequisite::query()
            ->where('course_id', $scheduledCourse->course_id)
            ->orderBy('id')
            ->value('prerequisite');

        $people = Person::query()
            ->select(['people.id', 'people.name', 'people.last_name', 'people.id_number', 'people.id_type_id'])
            ->whereHas('user', function ($query) {
                $query->where('role_id', Role::PARTICIPANTE);
            })
            ->withoutSessionConflict($scheduledCourse);

        if ($coursePrerequisite !== null) {
            $people->whereExists(function ($query) use ($coursePrerequisite) {
                $query->selectRaw('1')
                    ->from('participants as prerequisite_participants')
                    ->join('scheduled_course as prerequisite_scheduled', 'prerequisite_scheduled.id', '=', 'prerequisite_participants.scheduled_id')
                    ->join('courses as prerequisite_courses', 'prerequisite_courses.id', '=', 'prerequisite_scheduled.course_id')
                    ->whereColumn('prerequisite_participants.person_id', 'people.id')
                    ->where('prerequisite_participants.participant_status_id', Participant::APROBADO)
                    ->where('prerequisite_scheduled.course_status_id', CourseStatus::CULMINADO)
                    ->where('prerequisite_courses.code', $coursePrerequisite)
                    ->whereNull('prerequisite_participants.deleted_at')
                    ->whereNull('prerequisite_scheduled.deleted_at')
                    ->whereNull('prerequisite_courses.deleted_at');
            });
        }

        $searchTerm = trim((string) ($validated['q'] ?? ''));

        if ($searchTerm !== '') {
            $searchTerm = Funciones::normalizeSearchTerm($searchTerm);
            $isSqlite = $people->getConnection()->getDriverName() === 'sqlite';
            $searchTokens = preg_split('/\s+/', $searchTerm, -1, PREG_SPLIT_NO_EMPTY) ?: [$searchTerm];

            foreach ($searchTokens as $token) {
                $escapedToken = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $token);
                $pattern = '%' . $escapedToken . '%';
                $idToken = preg_replace('/\D+/', '', $token);

                $people->where(function ($query) use ($pattern, $idToken, $isSqlite) {
                    foreach (['people.name', 'people.last_name'] as $index => $column) {
                        $searchExpression = $isSqlite
                            ? 'lower(strip_accents(' . $column . ')) LIKE ? ESCAPE \'!\''
                            : $column . ' LIKE ? ESCAPE \'!\'';

                        if ($index === 0) {
                            $query->whereRaw($searchExpression, [$pattern]);
                        } else {
                            $query->orWhereRaw($searchExpression, [$pattern]);
                        }
                    }

                    if ($idToken !== '') {
                        $escapedIdToken = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $idToken);
                        $idPattern = '%' . $escapedIdToken . '%';
                        $query->orWhereRaw('people.id_number LIKE ? ESCAPE \'!\'', [$idPattern]);
                    }
                });
            }
        }

        $paginator = $people
            ->orderBy('people.last_name')
            ->orderBy('people.name')
            ->orderBy('people.id')
            ->simplePaginate(20, ['*'], 'page', (int) ($validated['page'] ?? 1));

        $results = collect($paginator->items())->map(function (Person $person) {
            $idType = (int) $person->id_type_id === 1 ? 'V' : 'E';
            $idNumber = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $person->id_number);

            return [
                'id' => $person->id,
                'text' => $person->name . ' ' . $person->last_name . ' C.I: ' . $idType . '-' . $idNumber,
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'more' => $paginator->hasMorePages(),
        ]);
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

            if ($cursoProg->isCancelado()) {
                return Redirect::back()
                    ->with("alert", Funciones::getAlert("warning", "Acción ya cancelada", "Esta acción de formación ya se encuentra cancelada."));
            }

            $cursoProg->course_status_id = CourseStatus::CANCELADO;

            if ($cursoProg->save()) {

                $cursoProg->participants()->update(['participant_status_id' => Participant::CANCELADO]);
            
                return Redirect::back()
                    ->with("alert", Funciones::getAlert("success", "Cancelado Exitosamente", "Operación Exitosa."));
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
                    ]);
            }

            if ($request->expectsJson()) {
                session()->flash("alert", Funciones::getAlert("success", "Acción de formación programada exitosamente", "Operación Exitosa."));

                return response()->json(['success' => true]);
            }

            return Redirect::back()
                ->with("alert", Funciones::getAlert("success", "Acción de formación programada exitosamente", "Operación Exitosa."));
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
            ->whereNull('deleted_at')
            ->whereHas('scheduled', function ($q) {
                $q->where('course_status_id', '!=', CourseStatus::CANCELADO);
            });

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
