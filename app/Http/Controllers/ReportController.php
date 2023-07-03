<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scheduled;
use App\Models\Course;
use App\Models\Participant;

class ReportController extends Controller
{
    public function byDate()
    {
        //By date

        //take date data as input
        //need date start and date end

        $scheduledCourse = Scheduled::where('start_date',$request->start_date)
        ->where('end_date',$request->end_date)->get();    
        
        //need a date range
        //separate dates by steps
            //grouping dates
        //count of courses by each step
        //make array data to send to js script

        return $scheduledCourse;
        /* 
        dd($scheduledCourse);
        return view('pages.admin.usuarios.reports')
        ->with('scheduledCourses',$scheduledCourse); */
    }
    public function byCategory(){
        //Quanity By Category
        $categoryCourse = Course::where('category_id', $request->category_id)->get();
        return $categoryCourse;
    }
    public function byStatus(){
        //Quantity By Status
        /* $statusCourse = Scheduled::with('course_status')
            ->where('course_status_id', $request->course_status_id)
            ->get(); */
        return Scheduled::with('courseStatus')->with('course')->get();
        //return $statusCourse;
    }
    public function byCourseTotalTime(){
        //Course Total Time
        $timeCourse = Course::with('scheduled')->sum('duration');
        return $timeCourse;
    }
    public function byCanceled(){
        //Canceled Courses
        $canceledCourse = Scheduled::withTrashed()->where('course_status_id', 4)->with('course')->get();
        return $canceledCourse;
    }
    public function participantsByStatus()
    {
        //Participants by Status
        $statusParticipant = Participant::where('participant_status_id')->get();
        return $statusParticipant;
    }
}
