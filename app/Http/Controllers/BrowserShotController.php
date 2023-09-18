<?php

namespace App\Http\Controllers;
use App\Models\Course;
use App\Models\Scheduled;
use App\Models\User;

use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class BrowserShotController extends Controller
{
    public function courses(Request $request){
        $path = public_path('storage/Lista de Acciones de Formación.pdf');
        $courses = Course::get();
        //$html = view('pdf.course' , ['courses' => $courses])->render();
        //$header= view('includes.admin.head')->render();
        $pdf = Browsershot::html(view('pdf.course' , ['courses' => $courses])->render())
        ->noSandbox()
        ->waitUntilNetworkIdle()
        ->emulateMedia('screen')
        ->footerHtml(view('pdf.footer')->render())
        ->showBrowserHeaderAndFooter()
        ->hideHeader()
        ->showBackground()
        ->margins(20, 10, 20, 10)
        ->savePdf($path);

        return response()->download($path)->deleteFileAfterSend(true);

    }

    public function scheduled(Request $request){
        $scheduled = Scheduled::with('course')->get();
        $path = public_path('storage/Lista de Acciones de Formación Programadas.pdf');
        $pdf = Browsershot::html(view('pdf.scheduled', ['scheduled' => $scheduled])->render())
        ->noSandbox()
        ->waitUntilNetworkIdle()
        ->emulateMedia('screen')
        ->footerHtml(view('pdf.footer')->render())
        ->showBrowserHeaderAndFooter()
        ->hideHeader()
        ->showBackground()
        ->margins(20, 10, 20, 10)
        ->savePdf($path);

        return response()->download($path)->deleteFileAfterSend(true);
        
    }

    public function users(Request $request){
        $users = User::get();
        $path = public_path('storage/Lista de Usuarios.pdf');
        $pdf = Browsershot::html(view('pdf.users', ['users' =>  $users])->render())
        ->noSandbox()
        ->waitUntilNetworkIdle()
        ->emulateMedia('screen')
        ->footerHtml(view('pdf.footer')->render())
        ->showBrowserHeaderAndFooter()
        ->hideHeader()
        ->showBackground()
        ->margins(20, 10, 20, 10)
        ->savePdf($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function facilitators(Request $request){
        $facilitators = User::where('role_id',4)->get();;
        $path = public_path('storage/Lista de Facilitadores.pdf');
        $pdf = Browsershot::html(view('pdf.facilitators', ['facilitators' => $facilitators])->render())
        ->noSandbox()
        ->waitUntilNetworkIdle()
        ->emulateMedia('screen')
        ->footerHtml(view('pdf.footer')->render())
        ->showBrowserHeaderAndFooter()
        ->hideHeader()
        ->showBackground()
        ->margins(20, 10, 20, 10)
        ->savePdf($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function participants(Request $request){
        $participants = User::where('role_id',5)->get();
        $path = public_path('storage/Lista de Participantes.pdf');
        $pdf = Browsershot::html(view('pdf.participants', ['participants' => $participants])->render())
        ->noSandbox()
        ->waitUntilNetworkIdle()
        ->emulateMedia('screen')
        ->footerHtml(view('pdf.footer')->render())
        ->showBrowserHeaderAndFooter()
        ->hideHeader()
        ->showBackground()
        ->margins(20, 10, 20, 10)
        ->savePdf($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function reportCourse(Request $request){
        $path = public_path('storage/Reporte - Acciones de Formación.pdf');

        return response()->download($path)->deleteFileAfterSend(true);
    }

}
