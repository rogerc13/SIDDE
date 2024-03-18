<?php

namespace App\Http\Controllers;
use App\Models\Course;
use App\Models\Scheduled;
use App\Models\User;

use Illuminate\Http\Request;

use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class BrowserShotController extends Controller
{
    public function courses(Request $request){

        //dd($request);

        $courses = Course::with('category')->where([
            ['category_id','=',$request->hidden_category],
            ['title','LIKE',"%$request->hidden_title%"]
        ])->get();

        dd($request);

        $pdf = Pdf::loadView('pdf.course',['courses' => $courses]);
        //return $pdf->stream();

        return $pdf->download('Lista de Acciones de Formación.pdf');

    }

    public function scheduled(Request $request){
        $scheduled = Scheduled::orderBy("start_date","desc")->with('course')->with('facilitator')->with('courseStatus');
         
        if($request->hidden_title){
            $hiddenTitle = $request->hidden_title;
            $scheduled = $scheduled->whereHas('course', function($q) use($hiddenTitle){
                        $q->where('title', 'like', '%'.$hiddenTitle.'%');
                    });
         }
         if($request->hidden_facilitator)
            $scheduled=$scheduled->where('facilitator_id','=',$request->hidden_facilitator);
         if($request->hidden_status){
             $scheduled= $scheduled->where('course_status_id',$request->hidden_status);
         }
         if($request->hidden_date){
            $fecha= new Carbon('01-'.$request->hidden_date);
            $scheduled = $scheduled->whereMonth('start_date',$fecha->month)
                             ->whereYear('end_date',$fecha->year);
            $fecha=$fecha->format('m-Y');
         }

         dd($request);

        $pdf = Pdf::loadView('pdf.scheduled', ['scheduled' => $scheduled]);
        return $pdf->download('Lista de Acciones de Formacion Programadas.pdf');
        
    }

    public function users(Request $request){
        $users = User::with('role')->with('person')->orderBy("id","asc");
        
        dd($request);
        
        if($request->hidden_name)
            $hiddenName = $request->hidden_name;
            $users = User::whereHas('person',function($query) use($hiddenName){
                return $query->where('name','LIKE',"%$hiddenName%");
            });
        if($request->hidden_surname)
            $hiddenSurname = $request->hidden_surname;
            $users = User::whereHas('person', function ($query) use ($hiddenSurname) {
                return $query->where('last_name', 'LIKE', "%$hiddenSurname%");
            });
        if($request->hidden_id && $request->hidden_id_type)
            $hiddenIDType = $request->hidden_type;
            $hiddenID = $request->hidden_id;
            $users = User::whereHas('person', function ($query) use ($hiddenID,$hiddenIDType) {
                return $query->where('id_type_id', '=', $hiddenIDType)->where('id_number', 'LIKE', "%$hiddenID%");
            });
        if($request->hidden_role)
            $users=$users->where('role_id','=',$request->hidden_role);

        dd($request);
        $pdf = Pdf::loadView('pdf.users', ['users' => $users]);
        return $pdf->download('Lista de Usuarios.pdf');
        
    }

    public function facilitators(Request $request){
        $facilitators = User::where('role_id',4)->get();;

        $facilitators = User::with('role')->with('person')->where('role_id','4');
        
        if($request->hidden_name){
            $hiddenName = $request->hidden_name;
            $facilitators=$facilitators->whereHas('person',function($query) use($hiddenName){
                $query->where('name','LIKE',"%$hiddenName%");
            });
        }

        if($request->hidden_surname){
            $hiddenSurname = $request->hidden_surname;
            $facilitators = $facilitators->whereHas('person', function ($query) use ($hiddenSurname) {
                $query->where('last_name', 'LIKE', "%$hiddenSurname%"); 
            });
        }

        if($request->hidden_id && $request->hidden_id_type){
            $hiddenIDType = $request->hidden_type;
            $hiddenID = $request->hidden_id;
            $facilitators = User::whereHas('person', function ($query) use ($hiddenID,$hiddenIDType) {
                return $query->where('id_type_id', '=', $hiddenIDType)->where('id_number', 'LIKE', "%$hiddenID%");
            });
        }

        $pdf = Pdf::loadView('pdf.facilitators', ['facilitators' => $facilitators]);
        return $pdf->download('Lista de Facilitadores.pdf');
    }

    public function participants(Request $request){
        $participants = User::where('role_id',5)->get();

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

        if($request->hidden_id && $request->hidden_id_type){
            $hiddenIDType = $request->hidden_type;
            $hiddenID = $request->hidden_id;
            $participants = User::whereHas('person', function ($query) use ($hiddenID,$hiddenIDType) {
                return $query->where('id_type_id', '=', $hiddenIDType)->where('id_number', 'LIKE', "%$hiddenID%");
            });
        }

        $pdf = Pdf::loadView('pdf.participants', ['participants' => $participants]);
        return $pdf->download('Lista de Participantes.pdf');
    }

    public function reportCourse(Request $request){
        $path = public_path('storage/Reporte - Acciones de Formación.pdf');

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function courseParticipants($id){
        //dd($id);
        $scheduled = Scheduled::with('course')->with('facilitator')->with('participants.person')->with('participants.participantStatus')->find($id);
        //dd($scheduled->participants[0]->person->name);
        $pdf = Pdf::loadView('pdf.course_participants',['scheduled' => $scheduled]);
        return $pdf->download($scheduled->course->title.' - Lista de Participantes.pdf');
    }
}
