<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Attendance;
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
            'lesson_name.unique' => 'Lesson '.$request['lesson_name'].' already exist, choose another name!',
            'lesson_description.required'   => 'Lesson description is required'
        ];

        // Validate the request
        $this->validate($request, [
            'lesson_name'  =>'required',
            'lesson_name' => 'unique:lessons,name',
            'lesson_description' =>'required'

        ], $messages);

        $post = $request->All();

        $lesson = new Lesson();

        $lesson->name = $post['lesson_name'];
        $lesson->description = $post['lesson_description'];

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
    public function update(UpdateLessonRequest $request, Lesson $lesson)
    {
        $messages = [
            'lesson_name.required' => 'Lesson name is required!',
            'lesson_description.required'   => 'Lesson description is required'
        ];

        // Validate the request
        $this->validate($request, [
            'lesson_name'  =>'required',
            'lesson_description' =>'required'

        ], $messages);

        $post = $request->All();

        $lesson = Lesson::find($post['lesson_id']);

        $lesson->name = $post['lesson_name'];
        $lesson->description = $post['lesson_description'];

        $lesson->save();

        Alert::toast('Lesson updated successifully', 'success');
        return redirect('/lessons');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $attendanceCount = Attendance::where('lesson_id', $id)->count();

        if($attendanceCount >= 1){

            $message ="There are attendances related to this course, lesson can not be deleted";
        }

        else{

            Lesson::find($id)->delete();
            $message ="Lesson deleted";
        }

        Alert::toast($message, 'warning');
        return redirect()->back();
    }
}
