<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Student;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $course = Course::with('Student')->get();
        return view('courses.courses', compact('course'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $instructor = Instructor::get();
        return view('courses.addcourse', compact('instructor'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $messages = [
            'course_name.required' => 'Course name is required!',
            'course_description.required'   => 'Course description is required',
            'course_price.required' => 'Price is required!',            
            'course_practicals.required' => 'Practicals number of days is required',
            'course_practicals.numeric' => 'Practicals number of days must be a number',
            'course_theory.numeric' => 'Theory number of days must be a number',
            'course_theory.required'   => 'Theory number of days is required',
        ];

        // Validate the request
        $this->validate($request, [
            'course_name'  =>'required',
            'course_description' =>'required',
            'course_price'   =>'required | numeric|min:0',
            'course_practicals' =>'required | numeric|min:0',
            'course_theory' =>'required | numeric|min:0'

        ], $messages);

        $post = $request->All();

        $course = new Course;
 
        $course->name = $post['course_name'];
        $course->short_description = $post['course_description'];
        $course->price = $post['course_price'];
        $course->duration = $post['course_practicals'] + $post['course_theory'];
        $course->practicals = $post['course_practicals'];
        $course->theory = $post['course_theory'];

        $course->save();

        return redirect('/courses')->with('message', 'New course added!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
       $course = Course::with('Instructor')->find($id);
       return view('courses.viewcourse', compact('course'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $course = Course::with('Instructor')->find($id);
        return view('courses.editcourse', compact('course'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Course $course)
    {
        $messages = [
            'course_name.required' => 'Course name is required!',
            'course_description.required'   => 'Course description is required',
            'course_price.required' => 'Price is required!',            
            'course_practicals.required' => 'Practicals number of days is required',
            'course_practicals.numeric' => 'Practicals number of days must be a number',
            'course_theory.numeric' => 'Theory number of days must be a number',
            'course_theory.required'   => 'Theory number of days is required',
        ];

        // Validate the request
        $this->validate($request, [
            'course_name'  =>'required',
            'course_description' =>'required',
            'course_price'   =>'required | numeric|min:0',
            'course_practicals' =>'required | numeric|min:0',
            'course_theory' =>'required | numeric|min:0'

        ], $messages);

        $post = $request->All();

        $course = Course::find($post['course_id']);
 
        $course->name = $post['course_name'];
        $course->short_description = $post['course_description'];
        $course->price = $post['course_price'];
        $course->duration = $post['course_practicals'] + $post['course_theory'];
        $course->practicals = $post['course_practicals'];
        $course->theory = $post['course_theory'];

        $course->save();

        return redirect('/courses')->with('message', 'Course updated successifully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $studentlist = Student::where('course_id', $id)->get();
        $studentCount = $studentlist->count();

        if($studentCount >= 1){

            $message ="There are students enrolled to this course, delete them first";
        }

        else{

            Course::find($id)->delete();
            $message ="Course deleted";
        }

        return redirect('/courses')->with('message', $message);
    }
}
