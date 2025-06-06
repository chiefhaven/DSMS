<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use App\Models\Course;

use function PHPUnit\Framework\isEmpty;

class studentProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $id = Auth::user()->student_id;

        // Retrieve the student along with related data
        $student = Student::with([
            'User',
            'Invoice',
            'Course.lessons', // Assuming Course has a lessons relationship
            'Attendance',
            'District',
            'Fleet',
            'Classroom',
        ])->find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $theoryCount = 0;
        $practicalCount = 0;

        if($student->Course){
            $theoryCount = optional($student->Course?->lessons)
                ->where('department_id', 'd9b69664-b8ca-11ef-9fee-525400adf70e')
                ->sum(fn($lesson) => $lesson->pivot->lesson_quantity ?? 0);

            $practicalCount = optional($student->Course?->lessons)
                ->where('department_id', 'd9b6a9c9-b8ca-11ef-9fee-525400adf70e')
                ->sum(fn($lesson) => $lesson->pivot->lesson_quantity ?? 0);
                //haven
        }

        // Add counts to the response
        $studentData = $student->toArray();
        $studentData['theoryCount'] = $theoryCount;
        $studentData['practicalCount'] = $practicalCount;

        return response()->json($studentData);
    }

    public function attendances()
    {
        $id = Auth::user()->student_id;

        // Retrieve the student along with related 'attendances' data
        $attendances = Attendance::with('lesson','instructor')->where('student_id', $id)->get();


        // Return the attendances data directly
        return response()->json($attendances);
    }

    public function courseDetails()
    {
        // Get the authenticated student's ID
        $id = Auth::user()->student->course_id;

        // Find the course for the student or fail with a custom error message
        $course = Course::find($id);

        if (!$course) {
            // If no course is found, return a custom error message with a 404 status
            return response()->json(['message' => 'Course not found'], 404);
        }
        // Count theory and practical lessons
        $theoryCount = $course->lessons->where('department_id', 'd9b69664-b8ca-11ef-9fee-525400adf70e')->sum('pivot.lesson_quantity');
        $practicalCount = $course->lessons->where('department_id', 'd9b6a9c9-b8ca-11ef-9fee-525400adf70e')->sum('pivot.lesson_quantity');

        // Add counts to the response
        $courseData = $course->toArray();
        $courseData['theoryCount'] = $theoryCount;
        $courseData['practicalCount'] = $practicalCount;

        // Return the course data in JSON format
        return response()->json($courseData);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function showAttendance()
    {
        $id = Auth::user()->student_id;
        $student = Attendance::With('Lesson', 'Instructor')->where('student_id', $id)->get();
        return response()->json($student);
    }

    public function courses()
    {
        $courses = Course::all();
        return response()->json($courses);
    }

    public function notifications()
    {
        $notifications = Auth::user()->notifications->get(10);
        return response()->json($notifications);
    }
}
