<?php

namespace App\Http\Controllers;

use App\Models\scheduleLesson;
use App\Http\Requests\StorescheduleLessonRequest;
use App\Http\Requests\UpdatescheduleLessonRequest;
use App\Models\ScheduleLesson as ModelsScheduleLesson;
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
        return view('attendances.scheduleLesson');

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
    public function update(UpdatescheduleLessonRequest $request, $id)
    {
        $scheduleLesson = ScheduleLesson::find($id);

        try {
            // Validate request (handled by UpdatescheduleLessonRequest)
            $validatedData = $request->validated();

            $scheduleLesson->update([
                'student_id' => $request['student_id'],
                'lesson_id' => $request['lesson_id'],
                'start_time' => $request['start_time'],
                'finish_time' => $request['finish_time'],
                'location' => $request['location'],
                'comments' => $request['comments'],
            ]);

            return response()->json([
                'message' => 'Schedule updated successfully',
                'schedule' => $scheduleLesson
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\scheduleLesson  $scheduleLesson
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $scheduleLesson = scheduleLesson::find($id);

        if (!$scheduleLesson) {
            return response()->json(['error' => 'Schedule not found'], 404); // Return a 404 if not found
        }

        try {
            $scheduleLesson->delete();
            return response()->json(['message' => 'Schedule deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete schedule', 'exception' => $e->getMessage()], 500);
        }
    }


    public function scheduleLesson()
    {
        $events = [];

        $lessonSchedules = scheduleLesson::with(['student', 'instructor', 'lesson'])->get();

        foreach ($lessonSchedules as $schedule) {
            // Ensure student name and lesson name are always properly set
            $studentName = ($schedule->student->fname ?? 'Unknown') . ' ' . ($schedule->student->sname ?? 'Student');
            $lessonName = $schedule->lesson->name ?? 'Unknown Lesson';

            $events[] = [
                'id' => $schedule->id,
                'title' => "$studentName ($lessonName)",
                'lesson' => $schedule->lesson,
                'location' => $schedule->location,
                'student' => $schedule->student,
                'start' => $schedule->start_time->format('Y-m-d H:i:s'),
                'end' => $schedule->finish_time->format('Y-m-d H:i:s'),
            ];
        }

        return response()->json($events, 200);
    }


}
