<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Student;
use App\Models\Invoice;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use App\Enums\ServerStatus;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CourseController extends Controller
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
        // Fetch all courses with their related lessons, students, and invoices
        $courses = Course::with(['lessons', 'student', 'invoice'])->get();

        // Add theory and practical lesson counts for each course
        $courses = $courses->map(function ($course) {
            // Count theory lessons
            $theoryCount = $course->lessons
                ->where('department.name', 'theory') // Filter lessons belonging to 'Theory' department
                ->sum('pivot.lesson_quantity'); // Use pivot table field for lesson_quantity

            // Count practical lessons
            $practicalCount = $course->lessons
                ->where('department.name', 'practical') // Filter lessons belonging to 'Practical' department
                ->sum('pivot.lesson_quantity'); // Use pivot table field for lesson_quantity

            // Add the counts as attributes to the course
            $course->theory_count = $theoryCount;
            $course->practical_count = $practicalCount;

            return $course;
        });

        // Group invoices by course_id
        $invoiceCount = Invoice::all()->groupBy('course_id');

        // Pass the courses and invoice counts to the view
        return view('courses.courses', compact('courses', 'invoiceCount'));
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
            //'course_practicals.required' => 'Practicals number of days is required',
            //'course_practicals.numeric' => 'Practicals number of days must be a number',
            //'course_theory.numeric' => 'Theory number of days must be a number',
            //'course_theory.required'   => 'Theory number of days is required',
            'course_code.required'   => 'Course Code is required',
        ];

        // Validate the request
        $this->validate($request, [
            'course_name'  =>'required',
            'course_description' =>'required',
            'course_price'   =>'required | numeric|min:0',
            //'course_practicals' =>'required | numeric|min:0',
            //'course_theory' =>'required | numeric|min:0',
            'course_code' =>'required', [Rule::enum('B','C1')]

        ], $messages);

        $post = $request->All();

        $course = new Course;

        $course->name = $post['course_name'];
        $course->class = $post['course_code'];
        $course->short_description = $post['course_description'];
        $course->price = $post['course_price'];
        $course->duration = 0;
        //$course->practicals = $post['course_practicals'];
        //$course->theory = $post['course_theory'];

        $course->save();
        Alert::toast('New course added successifully', 'success');
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
        // Fetch the course along with the related instructor
        $course = Course::with('Instructor', 'lessons')->find($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        // Get the count of theory lessons
        $theoryCount = $course->lessons()
        ->whereHas('department', function($query) {
            $query->where('name', 'Theory');
        })
        ->sum('course_lesson.lesson_quantity'); // Specify the pivot table

        // Get the count of practical lessons
        $practicalCount = $course->lessons()
        ->whereHas('department', function($query) {
            $query->where('name', 'Practical');
        })
        ->sum('course_lesson.lesson_quantity'); // Specify the pivot table

        // Return a JSON response with actual values
        return response()->json([
            'course' => $course, // course data
            'theoryCount' => $theoryCount, // theory lesson count
            'practicalCount' => $practicalCount, // practical lesson count
        ]);
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
            //'course_practicals.required' => 'Practicals number of days is required',
            //'course_practicals.numeric' => 'Practicals number of days must be a number',
            //'course_theory.numeric' => 'Theory number of days must be a number',
            //'course_theory.required'   => 'Theory number of days is required',
        ];

        // Validate the request
        $this->validate($request, [
            'course_name'  =>'required',
            'course_description' =>'required',
            'course_price'   =>'required | numeric|min:0',
            //'course_practicals' =>'required | numeric|min:0',
            //'course_theory' =>'required | numeric|min:0'

        ], $messages);

        $post = $request->All();

        $course = Course::find($post['course_id']);

        $course->name = $post['course_name'];
        $course->class = $post['course_code'];
        $course->short_description = $post['course_description'];
        $course->price = $post['course_price'];
        //$course->duration = $post['course_practicals'] + $post['course_theory'];
        //$course->practicals = $post['course_practicals'];
        //$course->theory = $post['course_theory'];

        $course->save();

        return redirect('/courses')->with('message', 'Course updated successifully!');
    }

    public function updateCourseLessons(Request $request, Course $course)
    {
        $messages = [
            'courseId.required' => 'Course is required!',
        ];

        // Validate the request
        $this->validate($request, [
            'courseId'  =>'required',

        ], $messages);

        $post = $request->all();

        // Retrieve the lessons and their quantities
        $lessons = $post['courseLessons'];

        // Find the course
        $course = Course::find($post['courseId']); // Find the course by its ID

        // Ensure course exists to avoid null errors
        if (!$course) {
            return response()->json(['error' => 'Course not found.'], 404);
        }

        // Prepare the lessons with pivot data
        $lessonData = [];

        foreach ($lessons as $lesson) {
            $lessonData[$lesson['id']] = [
                'id' => Str::uuid(), // Generate a unique UUID for the pivot table
                'lesson_quantity' => $lesson['lesson_quantity'], // Use lesson_quantity from the request
                'order' => $lesson['order'], // Use lesson_quantity from the request
            ];
        }

        // Attach or sync the lessons with pivot data
        $course->lessons()->sync($lessonData);

        $course->save();

        return response()->json(['message' => 'Course lessons updated successifully!']);
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
