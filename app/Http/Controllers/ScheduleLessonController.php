<?php

namespace App\Http\Controllers;

use App\Models\ScheduleLesson;
use App\Http\Requests\StorescheduleLessonRequest;
use App\Http\Requests\UpdatescheduleLessonRequest;
use App\Models\ScheduleLesson as ModelsScheduleLesson;
use App\Notifications\LessonScheduled;
use Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScheduleLessonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->hasRole('instructor')) {
            return redirect()->route('dashboard')->with('error', 'Access denied');
        }
        return view('schedules.scheduleLesson');

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
        // 1. Validate the request data
        $validator = Validator::make($request->all(), [
            'lesson_id'    => 'required|exists:lessons,id',
            'student_id'   => 'required|exists:students,id',
            'start_time'   => 'required|date|after_or_equal:now',
            'finish_time'  => 'required|date|after:start_time',
            'comments'     => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 3. Check if the time slot is available (no overlapping lessons)
        $isSlotTaken = ScheduleLesson::where('instructor_id', Auth::user()->instructor_id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->finish_time])
                    ->orWhereBetween('finish_time', [$request->start_time, $request->finish_time])
                    ->orWhere(function ($query) use ($request) {
                        $query->where('start_time', '<', $request->start_time)
                                ->where('finish_time', '>', $request->finish_time);
                    });
            })
            ->exists();

        if ($isSlotTaken) {
            return response()->json([
                'message' => 'This time slot is already booked.',
            ], 409);
        }

        // 4. Use a database transaction to ensure data consistency
        DB::beginTransaction();

        try {
            $schedule = ScheduleLesson::create([
                'lesson_id'    => $request->lesson_id,
                'instructor_id' => Auth::user()->instructor_id,
                'student_id'    => $request->student_id,
                'start_time'   => $request->start_time,
                'finish_time'  => $request->finish_time,
                'status'       => 'scheduled',
                'location' => $request->location,
                'comments'     => $request->comments,
            ]);

            // 5. Send notification to the student
            Notification::send(
                $schedule->student->user,
                new LessonScheduled(
                    $schedule
                )
            );

            DB::commit(); // Commit if everything succeeds

            return response()->json([
                'message'  => 'Lesson scheduled successfully',
                'schedule' => $schedule,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error

            return response()->json([
                'message' => 'Failed to schedule lesson',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function schedules()
    {
        if (Auth::user()->hasRole('instructor')) {
            return redirect()->route('dashboard')->with('error', 'Access denied');
        }
        return view('schedules.adminSchedules');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ScheduleLesson  $ScheduleLesson
     * @return \Illuminate\Http\Response
     */
    public function show(ScheduleLesson $ScheduleLesson)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ScheduleLesson  $ScheduleLesson
     * @return \Illuminate\Http\Response
     */
    public function edit(ScheduleLesson $ScheduleLesson)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatescheduleLessonRequest  $request
     * @param  \App\Models\ScheduleLesson  $ScheduleLesson
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
     * @param  \App\Models\ScheduleLesson  $ScheduleLesson
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ScheduleLesson = ScheduleLesson::find($id);

        if (!$ScheduleLesson) {
            return response()->json(['error' => 'Schedule not found'], 404); // Return a 404 if not found
        }

        try {
            $ScheduleLesson->delete();
            return response()->json(['message' => 'Schedule deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete schedule', 'exception' => $e->getMessage()], 500);
        }
    }


    public function scheduleLesson()
    {
        $events = [];

        if (Auth::user()->hasRole('instructor')) {
            // Fetch only lessons for the logged-in instructor
            $lessonSchedules = ScheduleLesson::with(['student', 'instructor', 'lesson'])
                ->where('instructor_id', Auth::user()->instructor_id)
                ->get();
        } else {
            // Fetch all lessons for other roles
            $lessonSchedules = ScheduleLesson::with(['student', 'instructor', 'lesson'])->get();
        }

        foreach ($lessonSchedules as $schedule) {
            // Ensure student name and lesson name are properly set
            $studentName = ($schedule->student->fname ?? 'Unknown') . ' ' .($schedule->student->mname ?? '') . ' ' . ($schedule->student->sname ?? 'Student');
            $instructorName = ($schedule->instructor->fname ?? 'Unknown') . ' ' . ($schedule->instructor->sname ?? 'Student');
            $lessonName = $schedule->lesson->name ?? 'Unknown Lesson';

            $events[] = [
                'id' => $schedule->id,
                'title' => "$studentName ($lessonName)",
                'lesson' => $schedule->lesson,
                'instructor'=> $instructorName,
                'location' => $schedule->location,
                'comments' => $schedule->comments,
                'student' => $schedule->student,
                'start' => $schedule->start_time->format('Y-m-d H:i:s'),
                'end' => $schedule->finish_time->format('Y-m-d H:i:s'),
            ];
        }

        return response()->json($events, 200);
    }



}
