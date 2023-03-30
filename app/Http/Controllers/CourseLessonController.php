<?php

namespace App\Http\Controllers;

use App\Models\course_lesson;
use App\Http\Requests\Storecourse_lessonRequest;
use App\Http\Requests\Updatecourse_lessonRequest;

class CourseLessonController extends Controller
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
     * @param  \App\Http\Requests\Storecourse_lessonRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Storecourse_lessonRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\course_lesson  $course_lesson
     * @return \Illuminate\Http\Response
     */
    public function show(course_lesson $course_lesson)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\course_lesson  $course_lesson
     * @return \Illuminate\Http\Response
     */
    public function edit(course_lesson $course_lesson)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Updatecourse_lessonRequest  $request
     * @param  \App\Models\course_lesson  $course_lesson
     * @return \Illuminate\Http\Response
     */
    public function update(Updatecourse_lessonRequest $request, course_lesson $course_lesson)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\course_lesson  $course_lesson
     * @return \Illuminate\Http\Response
     */
    public function destroy(course_lesson $course_lesson)
    {
        //
    }
}
