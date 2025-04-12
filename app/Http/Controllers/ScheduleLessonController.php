<?php

namespace App\Http\Controllers;

use App\Models\ScheduleLesson;
use App\Http\Requests\StorescheduleLessonRequest;
use App\Http\Requests\UpdatescheduleLessonRequest;
use App\Models\Lesson;
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
        if (!Auth::user()->hasRole('instructor')) {
            return response()->json([
                'message' => 'You are not eligible to edit or add a schedule',
            ], 409);
        }

        // 1. Validate the request data
        $validator = Validator::make($request->all(), [
            'selectedStudents' => 'required|array',
            'start_time'       => 'required|date|after_or_equal:now',
            'finish_time'      => 'required|date|after:start_time',
            'comments'         => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 2. Check for overlapping lessons
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

        DB::beginTransaction();

        try {
            // 3. Create the schedule
            $schedule = ScheduleLesson::create([
                'instructor_id' => Auth::user()->instructor_id,
                'start_time'    => $request->start_time,
                'finish_time'   => $request->finish_time,
                'comments'      => $request->comments,
            ]);

            // 4. Attach students with lesson data
            foreach ($request->selectedStudents as $student) {
                DB::table('schedule_lesson_students')->insert([
                    'id'          => \Illuminate\Support\Str::uuid(),
                    'schedule_id' => $schedule->id,
                    'student_id'  => $student['studentId'],
                    'lesson_id'   => $student['selectedLesson']['id'],
                    'location'    => $student['location'],
                    'status'      => 'scheduled',
                ]);

                // 5. Send notification to each student
                $studentModel = \App\Models\Student::with('user')->find($student['studentId']);
                if ($studentModel && $studentModel->user) {
                    Notification::send($studentModel->user, new LessonScheduled($schedule));
                }
            }

            DB::commit();

            return response()->json([
                'message'  => 'Lesson scheduled successfully',
                'schedule' => $schedule,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

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

    public function checkStudent(StorescheduleLessonRequest $request)
    {
        $post = $request->all();

        $scheduleId = $post['scheduleId'];
        $studentId = $post['studentId'];

        // Check if student is already in the schedule
        $scheduleSet = DB::table('schedule_lesson_students')
            ->where('student_id', $studentId)
            ->where('schedule_id', $scheduleId)
            ->exists(); // Using exists() for performance optimization

        if ($scheduleSet) {
            // Return error message if already scheduled
            return response()->json([
                'feedback' => 'error',
                'message' => "{$post['student']} is already scheduled."
            ], 200);
        }

        // Success message if student is not in the schedule
        return response()->json([
            'feedback' => 'success',
            'message' => 'Student added to list. Remember to click submit after selecting all students.'
        ], 200);
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
        if (!Auth::user()->hasRole('instructor')) {
            return response()->json([
                'message'  => 'You are not eligible to edit or add a schedule',
            ], 409);
        }

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
        if (!Auth::user()->hasRole('instructor')) {
            return response()->json([
                'message'  => 'You are not eligible to delte schedule',
            ], 409);
        }

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


    public function getLessonSchedules()
    {
        $events = [];

        // Fetch lesson schedules depending on user role
        if (Auth::user()->hasRole('instructor')) {
            $lessonSchedules = ScheduleLesson::with(['students', 'instructor', 'lesson'])
                ->where('instructor_id', Auth::user()->instructor_id)
                ->get();
        } else {
            $lessonSchedules = ScheduleLesson::with(['students', 'instructor', 'lesson'])->get();
        }

        foreach ($lessonSchedules as $schedule) {
            $studentsData = [];
            $studentNames = [];

            foreach ($schedule->students as $student) {
                $studentFullName = trim(
                    ($student->fname ?? '') . ' ' .
                    ($student->mname ?? '') . ' ' .
                    ($student->sname ?? '')
                );

                $studentNames[] = $studentFullName;

                $studentsData[] = [
                    'id' => $student->id,
                    'fname' => $student->fname,
                    'mname' => $student->mname,
                    'sname' => $student->sname,
                    'pivot' => [
                        'lesson' => lesson::find($student->pivot->lesson_id),
                        'location' => $student->pivot->location,
                        'status' => $student->pivot->status,
                    ]
                ];
            }

            $events[] = [
                'id' => $schedule->id,
                'title' => implode(', ', $studentNames) . ' (' . ($schedule->lesson->name ?? 'Lesson') . ')',
                'start' => $schedule->start_time->format('Y-m-d H:i:s'),
                'end' => $schedule->finish_time->format('Y-m-d H:i:s'),
                'extendedProps' => [
                    'students' => $studentsData,
                    'comments' => $schedule->comments,
                    'instructor' => $schedule->instructor,
                ]
            ];
        }

        return response()->json($events, 200);
    }

}
