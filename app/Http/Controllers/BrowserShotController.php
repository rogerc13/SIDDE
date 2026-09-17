<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Scheduled;
use App\Models\User;
use App\Models\Role;
use App\Models\Funciones;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class BrowserShotController extends Controller
{

    private function denyRoster()
    {
        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
    }

    public function categories(Request $request){
        if (Auth::user()->cannot('getAll', Category::class)) {
            return $this->denyRoster();
        }

        $categories = Category::orderBy("id","desc");
        
        if((empty($request->hidden_name)) == true){
            $categories = $categories->get();
        }else{
            if($request->hidden_name){
                $categories = $categories->where('name','LIKE',"%".Funciones::escapeLike($request->hidden_name)."%");
            }
            $categories = $categories->get();
        }

        $pdf = Pdf::loadView('pdf.category',['categories' => $categories]);
        return $pdf->download('Lista de Áreas de Conocimiento.pdf');
    }


    public function courses(Request $request){
        if (Auth::user()->cannot('getAll', Course::class)) {
            return $this->denyRoster();
        }

        $courses = Course::with('category');
        
        if((empty($request->hidden_title) && empty($request->hidden_category)) == true){
          $courses = $courses->get();
        }else{
            if($request->hidden_category){
                $courses = $courses->where('category_id','=',$request->hidden_category);
            }
            if($request->hidden_title){
                $courses = $courses->where('title','LIKE',"%".Funciones::escapeLike($request->hidden_title)."%");
            }

            $courses = $courses->get();
        }

        $pdf = Pdf::loadView('pdf.course',['courses' => $courses]);

        return $pdf->download('Lista de Acciones de Formación.pdf');

    }

    public function scheduled(Request $request){
        if (Auth::user()->cannot('getAll', Scheduled::class)) {
            return $this->denyRoster();
        }

        $scheduled = Scheduled::orderBy("start_date","desc")->with('course')->with('facilitator')->with('courseStatus');
        
        if((empty($request->hidden_title) && empty($request->hidden_facilitator) && empty($request->hidden_status) && empty($request->hidden_date)) == true){
            $scheduled = $scheduled->get();
        }else{
            if($request->hidden_title){
                $hiddenTitle = $request->hidden_title;
                $scheduled = $scheduled->whereHas('course', function($q) use($hiddenTitle){
                            $q->where('title', 'like', '%'.$hiddenTitle.'%');
                        });
            }
            if($request->hidden_facilitator){
                $scheduled=$scheduled->where('facilitator_id','=',$request->hidden_facilitator);
            }
            if($request->hidden_status){
                $scheduled= $scheduled->where('course_status_id',$request->hidden_status);
            }
            if($request->hidden_date){
                $fecha= new Carbon('01-'.$request->hidden_date);
                $scheduled = $scheduled->whereMonth('start_date',$fecha->month)
                                    ->whereYear('end_date',$fecha->year);
                $fecha=$fecha->format('m-Y');
            }
            $scheduled = $scheduled->get();
        } 

        $pdf = Pdf::loadView('pdf.scheduled', ['scheduled' => $scheduled]);
        return $pdf->download('Lista de Acciones de Formacion Programadas.pdf');
        
    }

    public function users(Request $request){
        if (Auth::user()->cannot('getAll', User::class)) {
            return $this->denyRoster();
        }

        $users = User::with('role')->with('person')->orderBy("id","asc");
        
        if((empty($request->hidden_name) && empty($request->hidden_surname) && empty($request->hidden_id) && empty($request->hidden_role)) == true){
            $users = $users->get();
        }else{
            if($request->hidden_name){
                $hiddenName = $request->hidden_name;
                $users = $users->whereHas('person',function($query) use($hiddenName){
                    $query->where('name','LIKE',"%".Funciones::escapeLike($hiddenName)."%");
                });
            }
            if($request->hidden_surname){
                $hiddenSurname = $request->hidden_surname;
                $users =$users->whereHas('person', function ($query) use ($hiddenSurname) {
                    $query->where('last_name', 'LIKE', "%".Funciones::escapeLike($hiddenSurname)."%");
                });
            }
            if($request->hidden_id){
                $hiddenIDType = $request->hidden_id_type;
                $hiddenID = (string)$request->hidden_id;
                $users = $users->whereHas('person', function ($query) use ($hiddenID,$hiddenIDType) {
                    $query->where('id_type_id', '=', $hiddenIDType)->where('id_number', 'LIKE', "%".Funciones::escapeLike($hiddenID)."%");
                });
            }
            if($request->hidden_role){
                $users=$users->where('role_id','=',$request->hidden_role);
            }
            $users = $users->get();            
        }

        $pdf = Pdf::loadView('pdf.users', ['users' => $users]);
        return $pdf->download('Lista de Usuarios.pdf');
        
    }

    public function facilitators(Request $request){
        if (Auth::user()->cannot('getAllFacilitador', User::class)) {
            return $this->denyRoster();
        }

        $facilitators = User::with('role')->with('person')->where('role_id',Role::FACILITADOR);
        
        if((empty($request->hidden_name) && empty($request->hidden_surname) && empty($request->hidden_id)) == true){
            $facilitators = $facilitators->get();
        }else{
            
            if(empty($request->hidden_name) == false){
                $hiddenName = $request->hidden_name;
                $facilitators=$facilitators->whereHas('person',function($query) use($hiddenName){
                    $query->where('name','LIKE',"%".Funciones::escapeLike($hiddenName)."%");
                });
            }
            
            if(empty($request->hidden_surname) == false){
                $hiddenSurname = $request->hidden_surname;
                $facilitators = $facilitators->whereHas('person', function ($query) use ($hiddenSurname) {
                    $query->where('last_name', 'LIKE', "%".Funciones::escapeLike($hiddenSurname)."%"); 
                });
            }
                
            if(empty($request->hidden_id) == false){
                $hiddenIDType = $request->hidden_id_type;
                $hiddenID = (string)$request->hidden_id;
                $facilitators = $facilitators->whereHas('person', function ($query) use ($hiddenID,$hiddenIDType) {
                    $query->where('id_type_id', '=', $hiddenIDType)->where('id_number', 'LIKE', "%".Funciones::escapeLike($hiddenID)."%");
                });
            }
            $facilitators = $facilitators->get();
        }
        
        $pdf = Pdf::loadView('pdf.facilitators', ['facilitators' => $facilitators]);
        return $pdf->download('Lista de Facilitadores.pdf');
    }

    public function participants(Request $request){
        if (Auth::user()->cannot('getAllParticipante', User::class)) {
            return $this->denyRoster();
        }

        $participants = User::where('role_id',Role::PARTICIPANTE);

        if((empty($request->hidden_name) && empty($request->hidden_surname) && empty($request->hidden_id)) == true){
            $participants = $participants->get();
        }else{
            if($request->hidden_name){
                $hiddenName = $request->hidden_name;
                $participants = $participants->whereHas('person',function($query) use($hiddenName){
                    $query->where('name','LIKE',"%".Funciones::escapeLike($hiddenName)."%");
                });
            }
    
            if($request->hidden_surname){
                $hiddenSurname = $request->hidden_surname;
                $participants = $participants->whereHas('person', function ($query) use ($hiddenSurname) {
                    $query->where('last_name', 'LIKE', "%".Funciones::escapeLike($hiddenSurname)."%"); 
                });
            }
    
            if($request->hidden_id){
                $hiddenIDType = $request->hidden_id_type;
                $hiddenID = (string)$request->hidden_id;
                $participants = $participants->whereHas('person', function ($query) use ($hiddenID,$hiddenIDType) {
                    $query->where('id_type_id', '=', $hiddenIDType)->where('id_number', 'LIKE', "%".Funciones::escapeLike($hiddenID)."%");
                });
            }
            $participants = $participants->get();
        }
 
        $pdf = Pdf::loadView('pdf.participants', ['participants' => $participants]);
        return $pdf->download('Lista de Participantes.pdf');
    }

    public function reportCourse(Request $request){
        if (Auth::user()->cannot('getAll', Scheduled::class)) {
            return $this->denyRoster();
        }

        $path = public_path('storage/Reporte - Acciones de Formación.pdf');

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function courseParticipants($id){ //prints list of participants in a given course
        if (Auth::user()->cannot('getAllPorCurso', \App\Models\Participant::class)) {
            return $this->denyRoster();
        }

        $scheduled = Scheduled::with(['course', 'facilitator', 'participants.person', 'participants.participantStatus'])->find($id);
        $pdf = Pdf::loadView('pdf.course_participants',['scheduled' => $scheduled]);
        return $pdf->download($scheduled->course->title.' - Lista de Participantes.pdf');
    }

    public function myCourses(Request $request){
        
        $user = Auth::user();
        $participant = $user->isParticipante();
        
        if($user->isParticipante()){ //user is participant
            
            $user->load('person.scheduled');
            $courses = $user->person->scheduled; //courses is a Collection
            
            $values = $courses->pluck('id');
            
            if(count($courses) > 0){
                $scheduled = Scheduled::with('facilitator')->with('course')->whereIn('id', $values)->orderByDesc('start_date'); //cursos needs to be Builder
                
            }else{
                $scheduled = Scheduled::with('facilitator')->with('course')->where('id', null)->orderByDesc('start_date');
            }

        }else{ //user is facilitator

            $user->load('person.facilitator');

            $scheduled = Scheduled::with('facilitator')->with('course')->where('facilitator_id', $user->person->facilitator->id)->orderByDesc('start_date');
        
        }

        //filters

        if((empty($request->hidden_title) && empty($request->hidden_facilitator) && empty($request->hidden_date)) == true){
            
            $scheduled = $scheduled->get();

            

        }else{
            if($request->hidden_title){
                
                $hiddenTitle = $request->hidden_title;
                $scheduled = $scheduled->whereHas('course', function($q) use($hiddenTitle){
                            $q->where('title', 'like', '%'.$hiddenTitle.'%');
                        });
            }
            if($request->hidden_facilitator){

                $scheduled=$scheduled->where('facilitator_id','=',$request->hidden_facilitator);
                
            }
            if($request->hidden_date){
                
                $fecha= new Carbon('01-'.$request->hidden_date);
                $scheduled = $scheduled->whereMonth('start_date',$fecha->month)
                                    ->whereYear('end_date',$fecha->year);
                
                                    $fecha=$fecha->format('m-Y');
            }

            $scheduled = $scheduled->get();
        }
        
        $pdf = Pdf::loadView('pdf.my_courses',['scheduled' => $scheduled,'participant' => $participant]);
        return $pdf->download("Mis Acciones de Formación Programadas.pdf");
        
    }

    public function personCourses(Request $request) {
        $personId = $request->person_id ?? $request->id;
        if (!$personId) {
            abort(404, 'ID de persona no proporcionado');
        }

        $authUser = Auth::user();
        if (!$authUser->isAdministrador() && !$authUser->isTecEducativa()) {
            if ($authUser->person_id != $personId) {
                abort(403, 'No tienes permisos para ver los cursos de esta persona.');
            }
        }

        $user = User::where('person_id', $personId)->with('role', 'person')->first();
        if (!$user) {
            abort(404, 'Usuario no encontrado');
        }

        $user->load('person.scheduled', 'person.facilitator');

        $isParticipant = $user->isParticipante();
        $isFacilitator = $user->isFacilitador();

        if ($isParticipant) {
            $courses = $user->person->scheduled ?? collect();
            $scheduledIds = $courses->pluck('id')->all();
            if (count($scheduledIds) > 0) {
                $scheduled = Scheduled::with('facilitator', 'course', 'participants')
                    ->whereIn('id', $scheduledIds)
                    ->orderByDesc('start_date');
            } else {
                $scheduled = Scheduled::with('facilitator', 'course', 'participants')->where('id', null)->orderByDesc('start_date');
            }
        } elseif ($isFacilitator) {
            $facilitatorId = $user->person->facilitator->id ?? null;
            $scheduled = Scheduled::with('facilitator', 'course', 'participants')
                ->where('facilitator_id', $facilitatorId)
                ->orderByDesc('start_date');
        } else {
            // Default: show nothing
            $scheduled = Scheduled::with('facilitator', 'course', 'participants')->where('id', null)->orderByDesc('start_date');
        }

        // Filters
        if (
            empty($request->hidden_title) &&
            empty($request->hidden_facilitator) &&
            empty($request->hidden_status) &&
            empty($request->hidden_date)
        ) {
            $scheduled = $scheduled->get();
        } else {
            if ($request->hidden_title) {
                $hiddenTitle = $request->hidden_title;
                $scheduled = $scheduled->whereHas('course', function ($q) use ($hiddenTitle) {
                    $q->where('title', 'like', '%' . $hiddenTitle . '%');
                });
            }
            if ($request->hidden_facilitator) {
                $scheduled = $scheduled->where('facilitator_id', $request->hidden_facilitator);
            }
            if ($request->hidden_status) {
                $scheduled = $scheduled->where('course_status_id', $request->hidden_status);
            }
            if ($request->hidden_date) {
                $fecha = new Carbon('01-' . $request->hidden_date);
                $scheduled = $scheduled->whereMonth('start_date', $fecha->month)
                    ->whereYear('end_date', $fecha->year);
            }
            $scheduled = $scheduled->get();
        }

        $pdf = Pdf::loadView('pdf.person_courses', [
            'scheduled' => $scheduled,
            'participant' => $isParticipant,
            'user' => $user
        ]);
        $fullName = trim(($user->person->name ?? '') . ' ' . ($user->person->last_name ?? ''));
        $fileName = 'Acciones_de_Formacion_de_' . ($fullName !== '' ? $fullName : 'usuario') . '.pdf';
        return $pdf->download($fileName);
    }
}
