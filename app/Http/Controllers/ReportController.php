<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scheduled;
use App\Models\Course;
use App\Models\Participant;

class ReportController extends Controller
{
    public function test()
    {
        //By date
        $scheduledCourse = Scheduled::where('start_date',$request->start_date)
        ->where('end_date',$request->end_date)->get();
        //Quanity By Category
        $categoryCourse = Course::where('category_id',$request->category_id)->get();
        //Quantity By Status
        $statusCourse = Scheduled::with('course_status')
        ->where('course_status_id',$request->course_status_id)
        ->get();
        //Canceled Courses
        $canceledCourse = Scheduled::where('course_status_id',4)->get();
        //Participants by Status
        $statusParticipant = Participant::where('participant_status_id')->get();
        //Course Total Time
        $timeCourse = Course::where('duration')->get();
        
        dd($scheduledCourse);
        return view('pages.admin.usuarios.reports')
        ->with('scheduledCourses',$scheduledCourse);

        return ;
    }
}
