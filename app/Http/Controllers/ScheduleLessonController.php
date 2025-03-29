<?php

namespace App\Http\Controllers;

use App\Models\scheduleLesson;
use App\Http\Requests\StorescheduleLessonRequest;
use App\Http\Requests\UpdatescheduleLessonRequest;
use Auth;

class ScheduleLessonController extends Controller
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
     * @param  \App\Http\Requests\StorescheduleLessonRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorescheduleLessonRequest $request)
    {
        try {
            $schedule = scheduleLesson::create([
                'course_id' => $request->course_id,
                'lesson_id' => $request->lesson_id,
                'instructor_id' => Auth::user()->instructor_id,
                'student_id' => $request->student_id,
                'start_time' => $request->start_time,
                'finish_time' => $request->finish_time,
                'status' => 'scheduled',
                'comments' => $request->comments,
            ]);

            return response()->json([
                'message' => 'Lesson scheduled successfully',
                'schedule' => $schedule
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error scheduling lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\scheduleLesson  $scheduleLesson
     * @return \Illuminate\Http\Response
     */
    public function show(scheduleLesson $scheduleLesson)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\scheduleLesson  $scheduleLesson
     * @return \Illuminate\Http\Response
     */
    public function edit(scheduleLesson $scheduleLesson)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatescheduleLessonRequest  $request
     * @param  \App\Models\scheduleLesson  $scheduleLesson
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatescheduleLessonRequest $request, scheduleLesson $scheduleLesson)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\scheduleLesson  $scheduleLesson
     * @return \Illuminate\Http\Response
     */
    public function destroy(scheduleLesson $scheduleLesson)
    {
        //
    }
}
