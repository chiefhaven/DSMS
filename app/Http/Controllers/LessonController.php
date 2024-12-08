<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Attendance;
use App\Models\Department;
use RealRashid\SweetAlert\Facades\Alert;

class LessonController extends Controller
{
    public function __construct()
    {

        $this->middleware(['role:superAdmin|admin']);

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
        $lessons = Lesson::with('department')->get();
        $departments = Department::get();
        return response()->json(['lessons' => $lessons, 'departments' => $departments], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('lessons.addlesson');
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
        ];

        // Validate the request
        $this->validate($request, [
            'lesson_name' => 'required|string',
            'lesson_description' => 'required|string',
            'lesson_department' => 'required|exists:departments,id',
        ], $messages);

        $post = $request->All();

        $lesson = new Lesson();

        $lesson->name = $post['lesson_name'];
        $lesson->description = $post['lesson_description'];
        $lesson->department_id = $post['lesson_department'];

        $lesson->save();

        Alert::toast('New lesson added successifully', 'success');
        return redirect('/lessons');
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
    public function edit($id)
    {
        $lesson = Lesson::find($id);
        return view('lessons.editlesson', compact('lesson'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateLessonRequest  $request
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLessonRequest $request, $lesson)
    {
        $messages = [
            'lesson_name.required' => 'Lesson name is required!',
            'lesson_description.required' => 'Lesson description is required!',
            'lesson_department.required' => 'Lesson type is required!',
            'lesson_department.exists' => 'The selected lesson type is invalid!',
        ];

        // Validate the request
        $this->validate($request, [
            'lesson_name' => 'required|string',
            'lesson_description' => 'required|string',
            'lesson_department' => 'required|exists:departments,id',
        ], $messages);

        $post = $request->All();

        $lesson = Lesson::find($lesson);

        $lesson->name = $post['lesson_name'];
        $lesson->description = $post['lesson_description'];
        $lesson->department_id = $post['lesson_department'];

        $lesson->save();

        return response()->json('Lesson updated successifully', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Check if the lesson exists
        $lesson = Lesson::find($id);

        if (!$lesson) {
            return response()->json([
                'message' => 'Lesson not found.'
            ], 404);
        }

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

}
