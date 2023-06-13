<?php

namespace App\Http\Controllers;

use App\Models\CourseFile;
use App\Http\Requests\StoreCourseFileRequest;
use App\Http\Requests\UpdateCourseFileRequest;

class CourseFileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreCourseFileRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCourseFileRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CourseFile  $courseFile
     * @return \Illuminate\Http\Response
     */
    public function show(CourseFile $courseFile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CourseFile  $courseFile
     * @return \Illuminate\Http\Response
     */
    public function edit(CourseFile $courseFile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCourseFileRequest  $request
     * @param  \App\Models\CourseFile  $courseFile
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCourseFileRequest $request, CourseFile $courseFile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CourseFile  $courseFile
     * @return \Illuminate\Http\Response
     */
    public function destroy(CourseFile $courseFile)
    {
        //
    }
}
