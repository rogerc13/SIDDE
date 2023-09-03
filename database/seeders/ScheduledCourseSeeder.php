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
        
        for ($i=0; $i < 4; $i++) {
                Scheduled::create([
                        'course_id' => 1,
                        'facilitator_id' => 1,
                        'course_status_id' => 1,
                        'start_date' => '2023-04-17',
                        'end_date' => '2023-04-27'
                ]);
                Scheduled::create([
                        'course_id' => 2,
                        'facilitator_id' => 1,
                        'course_status_id' => 2,
                        'start_date' => '2023-03-17',
                        'end_date' => '2023-03-27'
                ]);
                Scheduled::create([
                        'course_id' => 3,
                        'facilitator_id' => 1,
                        'course_status_id' => 1,
                        'start_date' => '2023-02-17',
                        'end_date' => '2023-02-27'
                ]);
                Scheduled::create([
                        'course_id' => 4,
                        'facilitator_id' => 1,
                        'course_status_id' => 3,
                        'start_date' => '2023-01-17',
                        'end_date' => '2023-02-10'
                ]);
                Scheduled::create([
                        'course_id' => 5,
                        'facilitator_id' => 1,
                        'course_status_id' => 4,
                        'start_date' => '2022-10-17',
                        'end_date' => '2022-11-17'
                ]);
                Scheduled::create([
                        'course_id' => 1,
                        'facilitator_id' => 1,
                        'course_status_id' => 1,
                        'start_date' => '2023-07-13',
                        'end_date' => '2023-07-17'
                ]);
        }
        
        
        
    }
}
