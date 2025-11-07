<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scheduled;
use App\Models\Course;
use App\Models\Participant;
use App\Models\ParticipantStatus;
use App\Models\Category;
use App\Models\CourseStatus;
use App\Models\Person;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;


class ReportController extends Controller
{
    protected function range($request){
        $dateRange = $request->date_range ?? '1';
        $step = $request->step ?? '1 month';

        // determine start date
        if ($dateRange !== 'all' && is_numeric($dateRange)) {
            $rangeStart = Carbon::now()->subMonths((int)$dateRange)->startOfDay();
        } elseif ($dateRange === 'all') {
            $first = Scheduled::orderBy('start_date')->value('start_date');
            $rangeStart = $first ? Carbon::parse($first)->startOfDay() : Carbon::now()->subYear()->startOfDay();
        } else {
            // fallback: treat as 1 month
            $rangeStart = Carbon::now()->subMonths(1)->startOfDay();
        }

        $currentDate = Carbon::now()->endOfDay();

        // Ensure step is a valid period string (examples: '1 day', '7 days', '1 month')
        try {
            $period = CarbonPeriod::create($rangeStart, $step, $currentDate);
        } catch (\Throwable $e) {
            // fallback to daily
            $period = CarbonPeriod::create($rangeStart, '1 day', $currentDate);
        }

        $date = [];
        foreach ($period as $d) {
            $date[] = $d->format('Y-m-d');
        }

        $day = (stripos($step, 'day') !== false && intval($step) === 1);
        $numberOfSteps = max(0, count($date) - 1);

        return (object)[
            'currentDate' => $currentDate->format('Y-m-d'),
            'interval' => $period,
            'date' => $date,
            'numberOfSteps' => $numberOfSteps,
            'day' => $day
        ];
    }

    public function participantStatus(){ //dynamic participant status selector

        $statuses = ParticipantStatus::all();
        return json_encode(['statuses' => $statuses]);
    }

    public function byDate(Request $request)
    {
        $datesObj = $this->range($request);
        $date = $datesObj->date;
        $numberOfSteps = $datesObj->numberOfSteps;
        $day = $datesObj->day;

        $xAxis = [];
        $y = [];

        $steps = max(1, $numberOfSteps);
        for ($k = 0; $k <= $steps; $k++) {
            $start_date = $date[$k];
            // if not daily, end is next date or same for last
            $end_index = ($day ? $k : min($k + 1, count($date) - 1));
            $end_date = $date[$end_index];

            $xAxis[] = Carbon::parse($start_date)->format('Y-m-d');
            $y[] = Scheduled::whereBetween('start_date', [$start_date, $end_date])->count();
        }

        return response()->json([
            'x' => $xAxis,
            'y' => $y,
            'total'=> array_sum($y),
            'yData' => [], // keep shape stable; populate if you need raw items
            'start_date' => $xAxis[0] ?? null,
            'end_date' => end($xAxis) ?: null
        ]);
    }//end course by date range

    public function byCategory(Request $request){
        //Course amount By Category

        // initialize arrays to avoid undefined variable notices
        $xAxis = [];
        $yAxis = [];
        $categories = [];
        $graphData = [];
        $courseData = [];

        $dates =  $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;
        $day = $dates->day;
        $i = 0;

        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$day === true ? $key : ($i < $numberOfSteps ? $i = $i + 1 : $i)];
            
            $xAxis[] = Carbon::parse($start_date)->format('Y-m-d');
            
        }
        foreach (Category::all() as $category) {
            foreach ($date as $key => $value) {

                $start_date = $date[$key];
                $end_date = $date[$day === true ? $key : ($i < $numberOfSteps ? $i = $i + 1 : $i)];

                $yAxis[] = [
                    'category' => $category->name,
                     'y' => Scheduled::with('course')
                        ->whereBetween(DB::raw('start_date'), array($start_date, $end_date))->whereHas(
                        'course',
                        function ($query) use ($category) {
                            $query->where('category_id', $category->id);
                        })->count(), 'x'=> Carbon::parse($start_date)->format('Y-m-d'),
                 ];    
            }
            $categories[] = $category->name;

            $graphData[] = [
                'category' => $category->name,
                'y' => Scheduled::whereBetween(DB::raw('start_date'), array($date[0], end($date)))->whereHas(
                        'course',
                        function ($query) use ($category) {
                            $query->where('category_id', $category->id);
                        }
                    )->select('start_date as y')->get(), 
                'x' => Scheduled::whereBetween(DB::raw('start_date'), array($date[0], end($date)))->whereHas(
                        'course',
                        function ($query) use ($category) {
                            $query->where('category_id', $category->id);
                        }
                    )->select('start_date as x')->get(),
            ];

            $courseData[] = ['categoryName'=> $category->name,
            'courseData' => Scheduled::with('course','course.category')->whereHas('course', function($query) use($category){
                $query->where('category_id',$category->id);
            })->whereBetween(DB::raw('start_date'), array($date[0], end($date)))->get(),
            'amount' => Scheduled::with('course')->whereHas('course', function ($query) use ($category) {
                    $query->where('category_id', $category->id);
                })->whereBetween(DB::raw('start_date'), array($date[0], end($date)))->count()];
        }

        $dateRangeHelper = ['startDate' => Carbon::parse($date[0])->format('Y-m-d'), 'endDate' => Carbon::parse(end($date))->format('Y-m-d')];
        
        return json_encode(['x' => $xAxis,
        'graphData'=>$graphData,
        'categories' => $categories,
        'courseData' => $courseData,
        'dateRange' => $dateRangeHelper,
        'y' => $yAxis]);

    }//end course by category

    public function byStatus(Request $request){
        //Courses By Status

        /* $statusCourse = Scheduled::with('courseStatus')
            ->where('course_status_id', $request->course_status_id)
            ->get(); */

        //by all time all course status no conditions, no time range
        $byAllTime = Scheduled::with('courseStatus')->select('course_status_id')->selectRaw('COUNT(*) as amount')
        ->groupBy('course_status_id')->orderByDesc('amount')->get();

        //by date range

        $dates =  $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;
        $day = $dates->day;
        $i = 0;

        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$day === true ? $key : ($i < $numberOfSteps ? $i = $i + 1 : $i)];

            $xAxis[] = Carbon::parse($start_date)->format('Y-m-d');
        }

        $i = 0;

        foreach (CourseStatus::all() as $status) {
           foreach($date as $key => $value){
                $start_date = $date[$key];
                $end_date = $date[$day === true ? $key : ($i < $numberOfSteps ? $i = $i + 1 : $i)];

                //Data to show graphs
                $byDateRange[] = [
                    'status' => $status->name,
                    'x' => Carbon::parse($start_date)->format('Y-m-d'),
                    'y' => Scheduled::where('course_status_id',$status->id)
                                ->whereBetween(DB::raw('start_date'), array($start_date, $end_date))->count()];
                
           } 
            $statusNames[] = $status->name;

            //Data to show course list by date range
            $courseData[] = ['statusName' => $status->name,
            'courseData' => Scheduled::with('course', 'courseStatus')
            ->where('course_status_id', $status->id)
            ->whereBetween(DB::raw('start_date'), array($date[0], end($date)
            ))->get(),
            'amount' => Scheduled::with('course', 'courseStatus')
                    ->where('course_status_id', $status->id)
                    ->whereBetween(DB::raw('start_date'), array(
                        $date[0], end($date)
            ))->count(),];
        }

        //by given status
            //$response = Scheduled::where('course_status_id',$request->status_id)->with('courseStatus')->count();
        
        $dateRangeHelper =['startDate' => Carbon::parse($date[0])->format('Y-m-d'), 'endDate' => Carbon::parse(end($date))->format('Y-m-d')]; 

        return json_encode(['statuses' => $statusNames,
                            'x' => $xAxis, 
                            'y' => $byDateRange , 
                            'byAllTime' => $byAllTime , 
                            'courseData' => $courseData,
                            'dateRange' => $dateRangeHelper]);

    }//end course by status

    public function byCourseDuration(Request $request){
        
        //ALL TIME
            //Course Total Time, no condition, all scheduled courses
            $byAllTime = Scheduled::with('course')->get()->pluck('course.duration')->sum();
            
            //total hours, finished courses, all time
            $finishedTotal = Scheduled::where('course_status_id',3)->with('course')->get()->pluck('course.duration')->sum();

            //course with the most duration in hours
            $mostDuration = Course::with('scheduled')->whereHas('scheduled')->orderBy('duration', 'desc')->get();
 
            //course that spans the most days, biggest difference between start_date and end_date
            $spansMostDays = Scheduled::with('course')
            ->select('id','start_date','end_date','course_id',DB::raw('DATEDIFF(end_date,start_date) AS max_difference'))
            ->orderByDesc('max_difference')
            ->get();

        //DATE RANGE
            //by date range , by status
            $dates =  $this->range($request);
            $date = $dates->date;
            $numberOfSteps = $dates->numberOfSteps;
            $day = $dates->day;
            $i = 0;

            foreach ($date as $key => $value) {
                $start_date = $date[$key];
                $end_date = $date[$day === true ? $key : ($i < $numberOfSteps ? $i = $i + 1 : $i)];

                $byDateRange[] = ['x' => Carbon::parse($start_date)->format('Y-m-d'),
                                'y' => collect(Scheduled::where('course_status_id', 3)->whereBetween(DB::raw('start_date'), array($start_date, $end_date))->with('course')->get())->sum(('course.duration')),
                                'data' => Scheduled::with('course')->whereBetween(DB::raw('start_date'), array($start_date, $end_date))->get(),
                                ];

                $byDateHelper[] = collect(Scheduled::where('course_status_id',3)
                ->whereBetween(DB::raw('start_date'),array($start_date, $end_date))
                ->with('course')
                ->get())
                ->sum(('course.duration'));
            }

            $dateHelper = collect($byDateHelper)->sum();

        $dateRangeHelper = ['startDate' => Carbon::parse($date[0])->format('Y-m-d'), 'endDate' => Carbon::parse(end($date))->format('Y-m-d')]; 

        
        return json_encode(['byAllTime' => $byAllTime,
        'finishedTotal' => $finishedTotal,
        'mostDuration' => $mostDuration,
        'byDateRange' => $byDateRange,
        'finishedByDateRange' => $dateHelper,
        'spansMostDays' => $spansMostDays,
        'dateRange' => $dateRangeHelper
        ]);

    }//end course duration

    public function byCanceled(){
        //Canceled Courses
        $canceledCourse = Scheduled::withTrashed()->where('course_status_id', 4)->with('course')->get();
        return $canceledCourse;
    }

    public function participantsByStatus(Request $request)
    {
        // initialize arrays and defaults
        $byAllTime = [];
        $notInCourse = [];
        $allStatusbyDateRange = [];
        $labels = [];
        $byStatusByDateRange = [];

        //all time by status
        $byAllTime = Participant::with('person','participantStatus')->where('participant_status_id', $request->participant_status)->get();
        //participants not in a course, always all time
        $notInCourse = Person::whereDoesntHave('participant')->whereHas('user',function($query){
            $query->where('role_id',5);
        })->select('id','name','last_name','id_number','phone')->get();
      
        //by date range by status
        $dates =  $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;
        $day = $dates->day;
        $i = 0;

        //return json_encode($date);
        foreach(ParticipantStatus::all() as $status){
            foreach ($date as $key => $value) {
                $start_date = $date[$key];
                $end_date = $date[$day === true ? $key : ($i < $numberOfSteps ? $i = $i + 1 : $i)];

                $allStatusbyDateRange[] = [
                    'date' => Carbon::parse($start_date)->format('Y-m-d'),
                    'countByStatus' => Participant::where('participant_status_id',$status->id)->whereHas(
                        'scheduled',
                        function ($query) use ($start_date, $end_date) {
                            $query->whereBetween(DB::raw('start_date'), array($start_date, $end_date));
                        }
                    )->get()->count(), 'status' => $status->name,
                ];
            }

            $labels[] = ['label' => $status->name,];
        }
        
        $j = 0;
        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$day === true ? $key : ($j < $numberOfSteps ? $j = $j + 1 : $j)];

            $byStatusByDateRange[] = [
                'date' => Carbon::parse($start_date)->format('Y-m-d'),
                'countByStatus' => Participant::where('participant_status_id', $request->participant_status)->whereHas(
                    'scheduled',
                    function ($query) use ($start_date, $end_date) {
                        $query->whereBetween(DB::raw('start_date'), array($start_date, $end_date));
                    }
                )->get()->count(), 'status' => $status->name,                
            ];
        }
        
        //return json_encode($byDateRange);
        
        return json_encode(['byAllTime' => $byAllTime, 'byStatusByDateRange'=> $byStatusByDateRange ,'allStatusbyDateRange' => $allStatusbyDateRange,'notInCourse' => $notInCourse, 'labels' => $labels,]);
    }//end by participant status

    public function courseByParticipantQuantity(Request $request){
        //ALL TIME
            //participant amount of participants all time no condition
            $amountAllTime = Participant::selectRaw('COUNT(*) as amount')->get();
            //participant amount by course, all time no condition
            $amountAllTimePerCourse = Participant::select('scheduled_id')->selectRaw('COUNT(*) AS count ')
                        ->groupBy('scheduled_id')->orderByDesc('count')->get();

        //BY DATE RANGE
        $dates =  $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;
        $day = $dates->day;
        $j = 0;

        //participant amount per course in a date range, no other condition
        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$day === true ? $key : ($j < $numberOfSteps ? $j = $j + 1 : $j)];
            foreach (Scheduled::with('course')->whereBetween(DB::raw('start_date'), array($start_date, $end_date))->get() as $scheduled) {
                $dateRangeAmountPerCourse[] = [
                    'date' => Carbon::parse($start_date)->format('Y-m-d'),
                    'scheduled_id' => $scheduled->id,
                    'course' => $scheduled->course->title,
                    'count' => Participant::where('scheduled_id', $scheduled->id)->count()
                ];
            }
        }

        $collected = collect($dateRangeAmountPerCourse);
        $sorted = $collected->sortByDesc('count')->values();

        return json_encode(['amountAllTime' => $amountAllTime, 
            'amountAllTimePerCourse' => $amountAllTimePerCourse, 
            'dateRangeAmountPerCourse' => isset($dateRangeAmountPerCourse) ? $sorted : 0]);
    }//end participant amount

    public function participantAverage(Request $request){

        //return json_encode('hello');
        $dates =  $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;
        $day = $dates->day;
        $j = 0;

        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$day === true ? $key : ($j < $numberOfSteps ? $j = $j + 1 : $j)];
            foreach(Scheduled::whereBetween(DB::raw('start_date'), array($start_date, $end_date))->get() as $scheduled) {
                $data[] = ['date' => Carbon::parse($start_date)->format('Y-m-d'),
                    'scheduled_id' => $scheduled->id,
                    'approved' => Participant::where('participant_status_id', 3)->where('scheduled_id',$scheduled->id)->count(),
                    'failed' => Participant::where('participant_status_id', 2)->where('scheduled_id', $scheduled->id)->count(),
                    'total' => Participant::where('scheduled_id',$scheduled->id)->count(),
                ];
           }
        }
        
        return json_encode(['data' => $data]);
        
    }//end participant average

    public function notScheduled(Request $request){
        //all courses not present on scheduled table
        $data = Course::with('scheduled','capacity','category')->whereDoesntHave('scheduled')->get();    
        return json_encode(['data' => $data]);
    }//end courses not scheduled
    
    public function mostScheduled(Request $request){
        //id of course that appears the most on scheduled table
        //get courses ids, count how many times each one repeats, get course data of each

        $amountData = Scheduled::get()->countBy('course_id');

        foreach ($amountData as $key => $value) {
            $array[] = $key;
         }

        $courseData = Course::whereIn('id',$array)->select('title','id','code')->get();


        return json_encode(['amountData' => $amountData, 'courseData' => $courseData]);
    }//end course most scheduled
}
