<?php

namespace App\Http\Controllers;

use App\Models\attendance_lesson;
use App\Http\Requests\Storeattendance_lessonRequest;
use App\Http\Requests\Updateattendance_lessonRequest;

class AttendanceLessonController extends Controller
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
     * @param  \App\Http\Requests\Storeattendance_lessonRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Storeattendance_lessonRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\attendance_lesson  $attendance_lesson
     * @return \Illuminate\Http\Response
     */
    public function show(attendance_lesson $attendance_lesson)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\attendance_lesson  $attendance_lesson
     * @return \Illuminate\Http\Response
     */
    public function edit(attendance_lesson $attendance_lesson)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Updateattendance_lessonRequest  $request
     * @param  \App\Models\attendance_lesson  $attendance_lesson
     * @return \Illuminate\Http\Response
     */
    public function update(Updateattendance_lessonRequest $request, attendance_lesson $attendance_lesson)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\attendance_lesson  $attendance_lesson
     * @return \Illuminate\Http\Response
     */
    public function destroy(attendance_lesson $attendance_lesson)
    {
        //
    }
}
