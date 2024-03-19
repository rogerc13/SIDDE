<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Models\Scheduled;


class ScheduledCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $weekFromNow = Carbon::today()->addWeek();
        $weekFromNowplusTenDays = $weekFromNow->addDays(10);

        $threeDaysAgo = Carbon::today()->subDays(3);

        $fiveDaysFromNow = $weekFromNow->subDays(2);
        $fifteenDaysFromNow = $fiveDaysFromNow->addDays(10);

        $monthAgo = Carbon::today()->subMonth();
        $monthAgoPlusTenDays = $monthAgo->addDays(10);

        $monthFromNow = Carbon::today()->addMonth();
        $monthFromNowPlusFiveDays = $monthFromNow->addDays(5);
        for ($i=0; $i < 4; $i++) {
                Scheduled::create([
                        'course_id' => 1, 
                        'facilitator_id' => 1,
                        'course_status_id' => 1, //por dictar
                        'start_date' => $weekFromNow->format('Y-m-d'), //a week from now
                        'end_date' => $weekFromNowplusTenDays->format('Y-m-d') // ten days from start date
                ]);
                Scheduled::create([
                        'course_id' => 2,
                        'facilitator_id' => 1,
                        'course_status_id' => 2, //en curso
                        'start_date' => $threeDaysAgo->format('Y-m-d'), //three days ago
                        'end_date' => $weekFromNow->format('Y-m-d') //ten days from start date
                ]);
                Scheduled::create([
                        'course_id' => 3,
                        'facilitator_id' => 1,
                        'course_status_id' => 1, //por dictar
                        'start_date' => $fiveDaysFromNow->format('Y-m-d'), //five days from now
                        'end_date' => $fifteenDaysFromNow->format('Y-m-d') //ten days from start date
                ]);
                Scheduled::create([
                        'course_id' => 4,
                        'facilitator_id' => 1,
                        'course_status_id' => 3, //culminado
                        'start_date' => $monthAgo->format('Y-m-d'), //a month ago
                        'end_date' => $monthAgoPlusTenDays->format('Y-m-d') // ten days from start date
                ]);
                Scheduled::create([
                        'course_id' => 5,
                        'facilitator_id' => 1,
                        'course_status_id' => 4, //cancelado
                        'start_date' => $monthAgo->format('Y-m-d'),//a month ago
                        'end_date' => $monthAgoPlusTenDays->format('Y-m-d')// ten days from start date
                ]);
                Scheduled::create([
                        'course_id' => 1,
                        'facilitator_id' => 1,
                        'course_status_id' => 1, //por dictar
                        'start_date' => $monthFromNow->format('Y-m-d'), //a month from now
                        'end_date' => $monthFromNowPlusFiveDays->format('Y-m-d') //five days from start date
                ]);
        }
        
        
        
    }
}
