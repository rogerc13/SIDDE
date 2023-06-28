<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Curso;
use App\Models\CursoProgramado;

class ReportController extends Controller
{
    public function test()
    {
        //Courses by dates;
        /* 
        $start_date = date($request->start_time);
        $end_date = date($request->end_time);
        */

        $inittime= date('2017-06-30');
        $finitttime= date('2023-06-17');
        $cursosProgramados = CursoProgramado::whereColumn([['fecha_f', '>=', 'fecha_i'], ['fecha_i', '<=', 'fecha_i']])->get();

        
        dd($cursosProgramados->curso);
        //dd($cursosProgramados);
        //return view('pages.admin.usuarios.reports')->with('cursosProgramados',$cursosProgramados);
        //return json_encode($cursosProgramados);
    }
}
