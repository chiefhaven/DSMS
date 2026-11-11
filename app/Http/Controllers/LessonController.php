<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Student;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class LessonController extends Controller
{
    public function __construct()
    {

        $this->middleware(['role:superAdmin|admin|instructor']);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lessons = Lesson::get();
        return view('lessons.lessons', compact('lessons'));
    }

    public function getLessons()
    {
        $lessons = Lesson::with('Department')->get()->map(function ($lesson) {
            return [
                'id' => $lesson->id,
                'name' => $lesson->name,
                'description' => $lesson->description,
                'department' => [
                    'id' => $lesson->Department->id ?? null,
                    'name' => $lesson->Department->name ?? null,
                ],
                'lesson_contents' => $lesson->lesson_contents->body->render(), // HTML string
            ];
        });

        $departments = Department::all(['id', 'name']);

        return response()->json([
            'lessons' => $lessons,
            'departments' => $departments,
        ], 200);
    }


    public function studentLessons($id)
    {
        $student = Student::with('course.lessons')->find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Access lessons via the related course
        $lessons = $student->course ? $student->course->lessons : [];

        return response()->json($lessons, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $departments = Department::all(['id', 'name']);

        return view('lessons.addlesson', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreLessonRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLessonRequest $request)
    {
        $messages = [
            'lesson_name.required' => 'Lesson name is required!',
            'lesson_description.required' => 'Lesson description is required!',
            'lesson_department.required' => 'Lesson department is required!',
            'lesson_department.exists' => 'The selected lesson department is invalid!',
            'lesson_contents.string' => 'Lesson content must be a valid string!',
        ];

        // Validate the request
        $this->validate($request, [
            'lesson_name' => 'required|string',
            'lesson_description' => 'required|string',
            'lesson_department' => 'required|exists:departments,id',
            'lesson_contents' => 'nullable|string',
        ], $messages);

        $post = $request->All();

        $lesson = new Lesson();

        $lesson->name = $post['lesson_name'];
        $lesson->description = $post['lesson_description'];
        $lesson->department_id = $post['lesson_department'];
        $lesson->lesson_contents = $post['lesson_contents'] ?? '';

        $lesson->save();

        Alert::toast('New lesson added successifully', 'success');
        return redirect()->route('lessons.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function show(Lesson $lesson)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, ?Lesson $lesson = null)
    {
        $lesson = $this->resolveLesson($request, $lesson);
        $departments = Department::all(['id', 'name']);

        return view('lessons.editlesson', compact('lesson', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateLessonRequest  $request
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLessonRequest $request, ?Lesson $lesson = null)
    {
        $lesson = $this->resolveLesson($request, $lesson);
        $messages = [
            'lesson_name.required' => 'Lesson name is required!',
            'lesson_description.required' => 'Lesson description is required!',
            'lesson_department.required' => 'Lesson type is required!',
            'lesson_department.exists' => 'The selected lesson type is invalid!',
            'lesson_contents.string' => 'Lesson content must be a valid string!',
        ];

        // Validate the request
        $this->validate($request, [
            'lesson_name' => 'required|string',
            'lesson_description' => 'required|string',
            'lesson_department' => 'required|exists:departments,id',
            'lesson_contents' => 'nullable|string',
        ], $messages);

        $post = $request->All();

        $lesson->name = $post['lesson_name'];
        $lesson->description = $post['lesson_description'];
        $lesson->department_id = $post['lesson_department'];
        $lesson->lesson_contents = $post['lesson_contents'] ?? '';

        $lesson->save();

        return response()->json('Lesson updated successifully', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, ?Lesson $lesson = null)
    {
        $lesson = $this->resolveLesson($request, $lesson);
        $id = $lesson->getKey();

        // Check for related attendances
        $attendanceCount = Attendance::where('lesson_id', $id)->count();

        if ($attendanceCount > 0) {
            return response()->json([
                'message' => 'There are attendances related to this lesson. The lesson cannot be deleted.'
            ], 400);
        }

        // Delete the lesson
        $lesson->delete();

        return response()->json([
            'message' => 'Lesson deleted successfully.'
        ], 200);
    }

    private function resolveLesson(Request $request, ?Lesson $lesson): Lesson
    {
        if ($lesson && $lesson->exists) {
            return $lesson;
        }

        return Lesson::findOrFail($request->route('lesson') ?? $request->route('id') ?? $request->input('lesson_id'));
    }
}
