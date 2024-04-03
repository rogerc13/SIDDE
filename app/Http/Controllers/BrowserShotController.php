<?php

namespace App\Http\Controllers;
use App\Models\Course;
use App\Models\Scheduled;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class BrowserShotController extends Controller
{
    public function courses(Request $request){

        $courses = Course::with('category');
        
        if((empty($request->hidden_title) && empty($request->hidden_category)) == true){
          $courses = $courses->get();
        }else{
            if($request->hidden_category){
                $courses = $courses->where('category_id','=',$request->hidden_category);
            }
            if($request->hidden_title){
                $courses = $courses->where('title','LIKE',"%$request->hidden_title%");
            }

            $courses = $courses->get();
        }

        $pdf = Pdf::loadView('pdf.course',['courses' => $courses]);

        return $pdf->download('Lista de Acciones de Formación.pdf');

    }

    public function scheduled(Request $request){

        $scheduled = Scheduled::orderBy("start_date","desc")->with('course')->with('facilitator')->with('courseStatus');
        
        if((empty($request->hidden_title) && empty($request->hidden_facilitador) && empty($request->hidden_status) && empty($request->hidden_date)) == true){
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
        $users = User::with('role')->with('person')->orderBy("id","asc");
        
        if((empty($request->hidden_name) && empty($request->hidden_surname) && empty($request->hidden_id) && empty($request->hidden_role)) == true){
            $users = $users->get();
        }else{
            if($request->hidden_name){
                $hiddenName = $request->hidden_name;
                $users = $users->whereHas('person',function($query) use($hiddenName){
                    $query->where('name','LIKE',"%$hiddenName%");
                });
            }
            if($request->hidden_surname){
                $hiddenSurname = $request->hidden_surname;
                $users =$users->whereHas('person', function ($query) use ($hiddenSurname) {
                    $query->where('last_name', 'LIKE', "%$hiddenSurname%");
                });
            }
            if($request->hidden_id){
                $hiddenIDType = $request->hidden_id_type;
                $hiddenID = (string)$request->hidden_id;
                $users = $users->whereHas('person', function ($query) use ($hiddenID,$hiddenIDType) {
                    $query->where('id_type_id', '=', $hiddenIDType)->where('id_number', 'LIKE', "%$hiddenID%");
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
        $facilitators = User::with('role')->with('person')->where('role_id','4');
        
        if((empty($request->hidden_name) && empty($request->hidden_surname) && empty($request->hidden_id)) == true){
            $facilitators = $facilitators->get();
        }else{
            
            if(empty($request->hidden_name) == false){
                $hiddenName = $request->hidden_name;
                $facilitators=$facilitators->whereHas('person',function($query) use($hiddenName){
                    $query->where('name','LIKE',"%$hiddenName%");
                });
            }
            
            if(empty($request->hidden_surname) == false){
                $hiddenSurname = $request->hidden_surname;
                $facilitators = $facilitators->whereHas('person', function ($query) use ($hiddenSurname) {
                    $query->where('last_name', 'LIKE', "%$hiddenSurname%"); 
                });
            }
                
            if(empty($request->hidden_id) == false){
                $hiddenIDType = $request->hidden_id_type;
                $hiddenID = (string)$request->hidden_id;
                $facilitators = $facilitators->whereHas('person', function ($query) use ($hiddenID,$hiddenIDType) {
                    $query->where('id_type_id', '=', $hiddenIDType)->where('id_number', 'LIKE', "%$hiddenID%");
                });
            }
            $facilitators = $facilitators->get();
        }
        
        $pdf = Pdf::loadView('pdf.facilitators', ['facilitators' => $facilitators]);
        return $pdf->download('Lista de Facilitadores.pdf');
    }

    public function participants(Request $request){
        $participants = User::where('role_id',5);

        if((empty($request->hidden_name) && empty($request->hidden_surname) && empty($request->hidden_id)) == true){
            $participants = $participants->get();
        }else{
            if($request->hidden_name){
                $hiddenName = $request->hidden_name;
                $participants = $participants->whereHas('person',function($query) use($hiddenName){
                    $query->where('name','LIKE',"%$hiddenName%");
                });
            }
    
            if($request->hidden_surname){
                $hiddenSurname = $request->hidden_surname;
                $participants = $participants->whereHas('person', function ($query) use ($hiddenSurname) {
                    $query->where('last_name', 'LIKE', "%$hiddenSurname%"); 
                });
            }
    
            if($request->hidden_id){
                $hiddenIDType = $request->hidden_id_type;
                $hiddenID = (string)$request->hidden_id;
                $participants = $participants->whereHas('person', function ($query) use ($hiddenID,$hiddenIDType) {
                    $query->where('id_type_id', '=', $hiddenIDType)->where('id_number', 'LIKE', "%$hiddenID%");
                });
            }
            $participants = $participants->get();
        }
 
        $pdf = Pdf::loadView('pdf.participants', ['participants' => $participants]);
        return $pdf->download('Lista de Participantes.pdf');
    }

    public function reportCourse(Request $request){
        $path = public_path('storage/Reporte - Acciones de Formación.pdf');

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function courseParticipants($id){ //prints list of participants in a given course
        //dd($id);
        $scheduled = Scheduled::with('course')->with('facilitator')->with('participants.person')->with('participants.participantStatus')->find($id);
        //dd($scheduled->participants[0]->person->name);
        $pdf = Pdf::loadView('pdf.course_participants',['scheduled' => $scheduled]);
        return $pdf->download($scheduled->course->title.' - Lista de Participantes.pdf');
    }

    public function myCourses(Request $request){
        
        $user = Auth::user();
        
        if($user->isParticipante()){ //user is participant
            
            $user = Auth::user();
            $courses = $user->person->scheduled; //courses is a Collection
            
            foreach ($courses as $course) {
                $values[] = $course->id;
            }
            
            if(count($courses) > 0){
                $scheduled = Scheduled::with('facilitator')->with('course')->whereIn('id', $values); //cursos needs to be Builder
                
            }else{
                $scheduled = Scheduled::with('facilitator')->with('course')->where('id', null);
            }

        }else{ //user is facilitator

            $user = Auth::user();

            $scheduled = Scheduled::with('facilitator')->with('course')->where('facilitator_id', $user->person->facilitator->id);
        
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

        $pdf = Pdf::loadView('pdf.my_courses',['scheduled' => $scheduled]);
        return $pdf->download("Mis Acciones de Formación Programadas.pdf");
        
    }
}
