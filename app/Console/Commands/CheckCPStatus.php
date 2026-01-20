<?php

namespace App\Console\Commands;

use App\Models\CourseStatus;
use App\Models\Scheduled;
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

        $cursos = Scheduled::orderBy("start_date","desc")->get();

      foreach ($cursos as $curso){

            if ($curso->course_status_id != CourseStatus::CANCELADO) {
                if (today() < $curso->start_date) {
                    if ($curso->course_status_id != CourseStatus::POR_DICTAR) {
                        $curso->course_status_id = CourseStatus::POR_DICTAR;
                        $curso->save();
                    }
                }
                else if (today() <= $curso->end_date) {
                    if ($curso->course_status_id != CourseStatus::EN_CURSO) {
                        $curso->course_status_id = CourseStatus::EN_CURSO;
                        $curso->save();
                    }

                    if($curso->participants->count() > 0){
                        foreach($curso->participants as $participant){
                            if($participant->participant_status_id == 5){
                                $participant->participant_status_id = 1;
                                $participant->save();
                            }
                        }
                    }

                }
                else if (today() > $curso->end_date){
                    if ($curso->course_status_id != CourseStatus::CULMINADO) {
                        $curso->course_status_id = CourseStatus::CULMINADO;
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
