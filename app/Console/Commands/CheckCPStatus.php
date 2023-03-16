<?php

namespace App\Console\Commands;

use App\Models\CPStatus;
use App\Models\CursoProgramado;
use Illuminate\Console\Command;

class CheckCPStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:cpstatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check el status de los cursos programados cada x horas';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        $cursos = CursoProgramado::orderBy("fecha_i","desc")->get();

      foreach ($cursos as $curso){

            if ($curso->status_id != CPStatus::CANCELADO) {
                if (today() < $curso->fecha_i) {
                    if ($curso->status_id != CPStatus::POR_DICTAR) {
                        $curso->status_id = CPStatus::POR_DICTAR;
                        $curso->save();
                    }
                }
                else if (today() <= $curso->fecha_f) {
                    if ($curso->status_id != CPStatus::EN_CURSO) {
                        $curso->status_id = CPStatus::EN_CURSO;
                        $curso->save();
                    }
                }
                else if (today() > $curso->fecha_f){
                    if ($curso->status_id != CPStatus::CULMINADO) {
                        $curso->status_id = CPStatus::CULMINADO;
                        $curso->save();
                    }
                }
            }

        }

        //dd('Aqui');
        //return 'Aqui';
        //return Command::SUCCESS;
    }
}
