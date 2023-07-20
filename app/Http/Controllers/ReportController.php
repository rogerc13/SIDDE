<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scheduled;
use App\Models\Course;
use App\Models\Participant;
use App\Models\Category;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;


class ReportController extends Controller
{
    protected function range($request){
        //get time range
        $dateRange = $request->date_range;
        //get steps
        $step = $request->step;
        if ($dateRange !== 'all') {
            $rangeStart =  Carbon::now()->subMonth('' . $dateRange . '')->format('Y-m-d');
        } else {
            $rangeStart = Scheduled::select('start_date')->first();
            $rangeStart = Carbon::parse($rangeStart->start_date)->format('Y-m-d');
        }

        $currentDate =  Carbon::now()->format('Y-m-d');

        $interval = CarbonPeriod::create($rangeStart, '' . $step . '', $currentDate);

        foreach ($interval as $key => $dates) {
            $date[] = $dates->toDateTimeString();
        }
        $numberOfSteps = count($date) - 1;
        return ((object)['currentDate' => $currentDate,'interval'=>$interval,'date'=>$date,'numberOfSteps'=>$numberOfSteps]);
        
    }   

    public function byDate(Request $request)
    {
    //By date
    //get status variable
        //$courseStatus = $request->course_status; //canceled, finished, active
        $dates =  $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;
        $i = 0;

        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$i < $numberOfSteps ? $i = $i + 1 : $i];
            
             $data[] = ['x' =>  Carbon::parse($start_date)->getPreciseTimestamp(3),
                 'y' => Scheduled::whereBetween(DB::raw('start_date'), array($start_date,$end_date))->count(),];
            $amountPerStep[] = Scheduled::whereBetween(DB::raw('start_date'), array($start_date, $end_date))->count();
        }
        
        $total = array_sum($amountPerStep);
        //should return amount in a single number of courses queried
        return json_encode(['data' => $data,'total'=>$total]);
    }//end course by date range

    public function byCategory(Request $request){
        //Course amount By Category

        /* $categories = Category::all();
        foreach ($categories as $category){
            $categoryCourse[] = [
                'id' => $category->id,
                'name' => $category->name,
            ];
        }

        for ($i=1; $i <=(Category::count()) ; $i++) { 

            $categoryCourse[] = ['date' => Carbon::parse($start_date)->format('Y-m-d'),
                'amount ' => Scheduled::whereBetween(DB::raw('start_date'), array($start_date, $end_date))->where('category_id',$i)->count(),];
        } */

        $sixMonthsAgo =  Carbon::now()->subMonth('6')->format('Y-m-d');
        $currentDate =  Carbon::now()->format('Y-m-d');

        $monthlyInterval = CarbonPeriod::create($sixMonthsAgo, '1 month', $currentDate);

        foreach ($monthlyInterval as $key => $dates) {
            $date[] = $dates->toDateTimeString();
        }

        $i = 0;
        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$i < 6 ? $i = $i + 1 : $i];

            foreach (Category::all() as $category) {
                $monthlyAmount[] = ['date' => Carbon::parse($start_date)->format('Y-m-d'),
                ''.$category->name.'' => Scheduled::with('course')->whereBetween(DB::raw('start_date'), array($start_date, $end_date))->whereHas(
                    'course', function($query) use($category){
                                $query->where('category_id', $category->id);
                    })->count(),];                
            }
        }
        foreach(Category::all() as $category){
            $categories[] = ['name' => $category->name];
        }
        return json_encode(['amount' => $monthlyAmount,'names' => $categories]);
    }//end course by category

    public function byStatus(){
        //Courses By Status

        /* $statusCourse = Scheduled::with('courseStatus')
            ->where('course_status_id', $request->course_status_id)
            ->get(); */

        //by all time all course status no conditions
        $byAllTime = Scheduled::with('courseStatus')->select('course_status_id')->selectRaw('COUNT(*) as amount')
        ->groupBy('course_status_id')->orderByDesc('amount')->get();

        //by date range

        $sixMonthsAgo =  Carbon::now()->subMonth('6')->format('Y-m-d');
        $currentDate =  Carbon::now()->format('Y-m-d');

        $monthlyInterval = CarbonPeriod::create($sixMonthsAgo, '1 month', $currentDate);

        foreach ($monthlyInterval as $key => $dates) {
            $date[] = $dates->toDateTimeString();
        }

        $i = 0;

        /* foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$i < 6 ? $i = $i + 1 : $i];
        
            $byDateRange[] = ['date' => Carbon::parse($start_date)->format('Y-m-d'),
                             'amount' => Scheduled::select('course_status_id')->selectRaw('COUNT(*) as amount')
                                ->groupBy('course_status_id')->orderByDesc('amount')
                                ->whereBetween(DB::raw('start_date'), array($start_date, $end_date))->()];
        }
 */
        //by given status
        //$response = Scheduled::where('course_status_id',$request->status_id)->with('courseStatus')->count();

        return json_encode(['byAllTime' => $byAllTime]);
    }//end course by status

    public function byCourseDuration(Request $request){
        //Course Total Time, no condition
        $byAllTime = Course::with('scheduled')->select('duration')->sum('duration');

        //by date range , finished courses
        $dates = $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;
        $byDateRange =[];

        $i = 0;

        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$i < $numberOfSteps ? $i = $i + 1 : $i];

            $byDateRange[] = ['date' => Carbon::parse($start_date)->format('Y-m-d'),
                            'duration' => Course::select('duration')->whereHas(
                'scheduled', function($query) use($start_date,$end_date){
                    $query->where('course_status_id', 3)->whereBetween(DB::raw('start_date'), array($start_date, $end_date));
                }
                )->sum('duration'),];

        }
        return json_encode(['byAllTime' => $byAllTime, 'byDateRange' => $byDateRange]);
    }//end course duration

    public function byCanceled(){
        //Canceled Courses
        $canceledCourse = Scheduled::withTrashed()->where('course_status_id', 4)->with('course')->get();
        return $canceledCourse;
    }

    public function participantsByStatus(Request $request)
    {
        //by all time
        $byAllTime = Participant::where('participant_status_id', 1 /* $request->status */)->count();

        //by date range

        $dates = $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;
        $byDateRange = [];
        
        $i = 0;

        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$i < $numberOfSteps ? $i = $i + 1 : $i];
            $byDateRange[] = ['date' => Carbon::parse($start_date)->format('Y-m-d'),
                    'byStatus' => Participant::with('scheduled')->select('scheduled_id')->whereHas(
                        'scheduled', function($query) use($start_date, $end_date){
                            $query->whereBetween('start_date', array($start_date,$end_date));
                        } 
                    )->count()];
        }

        //by given participant status all time
        $byStatusAllTime = Participant::all();/* where()->get(); */

        
        return json_encode(['byAllTime' => $byAllTime, 'byDateRange' => $byDateRange]);
    }//end by participant status

    public function participantByQuantity(Request $request){

        //by all time
        //with per scheduled course
        $response = Participant::select('scheduled_id')->selectRaw('COUNT(*) AS count ')
                    ->groupBy('scheduled_id')->orderByDesc('count')->get();

        //amount all time
        //$response = Participant::count();

        //by date range
        $dates = $this->range($request);
        $date = $dates->date;
        $numberOfSteps = $dates->numberOfSteps;

        $i = 0;
        foreach ($date as $key => $value) {
            $start_date = $date[$key];
            $end_date = $date[$i < $numberOfSteps ? $i = $i + 1 : $i];
            
                $monthlyAmount[] = [
                    /* 'date' => Carbon::parse($start_date)->format('Y-m-d'),
                    'participant_amount' => Scheduled::with('participants')->whereBetween(DB::raw('start_date'), array($start_date, $end_date))->whereHas(
                        'participants',
                        function ($query)  {
                            $query->select('scheduled_id')->groupBy('scheduled_id')->orderByRaw('COUNT(*) DESC');
                        }
                    )->count(), */
                    'date' => Carbon::parse($start_date)->format('Y-m-d'),
                    'participantAmount' => Participant::with('scheduled')->select('scheduled_id')->selectRaw('COUNT(*) as amount')->whereHas(
                        'scheduled', function($query) use($start_date,$end_date){
                            $query->whereBetween(DB::raw('start_date'), array($start_date, $end_date));
                        }
                    )->count(),
                ];
            

        }
        return json_encode(['byAllTime' => $response , 'byDateRange'=>$monthlyAmount]);
    }//end participant amount

    public function participantAverage(Request $request){

        $approved = Participant::withTrashed()->where('participant_status_id',3)->count();        
        $failed = Participant::withTrashed()->where('participant_status_id',2)->count();
        $total = $approved + $failed;
        $averageApproved = $failed / $total;
        $averageFailed = $approved / $total;
        $byAllTime = [];
        $byDateRange = []; 
        
        //return json_encode(['byallTime' => $byAllTime, 'byDateRange' => $byDateRange]);
        return json_encode(['total'=>$total,'averageApproved'=>$averageApproved , 'averageFailed' => $averageFailed]);
    }//end participant average
}
