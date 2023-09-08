<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Instructor;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use Auth;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $attendance = Attendance::with('Student', 'Lesson')->orderBy('attendance_date', 'DESC')->paginate(10);
        return view('attendances.attendances', compact('attendance'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $lesson = Lesson::get();
        $student = Student::get();
        $instructor = Instructor::get();
        return view('attendances.addattendance', compact('student','lesson', 'instructor'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreAttendanceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAttendanceRequest $request)
    {
        $messages = [
            'date.required' => 'Date is required!',
            'student.required'   => 'Student is required!',
            'lesson.required' => 'Lesson is required!',
            'instructor.required' => 'Instructor is required!',
        ];

        // Validate the request
        $this->validate($request, [
            'date'  =>'required',
            'student' =>'required',
            'lesson'   =>'required',
            'instructor'   =>'required'


        ], $messages);

        $post = $request->All();
        $student_id = havenUtils::studentID($post['student']);

        //Check course days and compare with attendance
        $courseID = Invoice::where('student_id', $student_id)->firstOrFail()->course_id;

        $student = Student::find($student_id);

        if(!isset($courseID)){
            Alert::toast($student->fname.' not enrolled to any course yet!', 'warning');
        }

        $courseDuration = havenUtils::courseDuration($courseID);

        if(self::attendanceCount($student_id) >= $courseDuration){
            Alert::toast('You can not enter more attendances than course duration a student enrolled!', 'warning');
            return redirect('/attendances');
        }

        $instructor_id = havenUtils::instructorID($post['instructor']);
        $lesson_id = havenUtils::lessonID($post['lesson']);

        $attendance = new Attendance;

        if(is_null($student_id)){

            Alert::toast('No such student is registered with us!', 'error');
            return redirect()->back()->withInput();
        }

        else{

            $attendance->student_id = $student_id;
        }

        $attendance->attendance_date = $post['date'];
        $attendance->lesson_id = $lesson_id;
        $attendance->instructor_id = $instructor_id;
        $attendance->entered_by = Auth::user()->name;

        $attendance->save();

        if($attendance->save()){

            if($courseDuration-self::attendanceCount($student_id)==0){
                $student->status = 'Finished';
                $message = 'This marks course completion for '.$student->fname.' '.$student->sname;
            }

            else{
                $student->status = 'In progress';
                $message = 'Attendance added successifuly!';
            }

            $student->save();
        }

        Alert::toast($message, 'success');
        return redirect('/attendances');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function show(Attendance $attendance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $student = Student::with('User')->find($id);
        $lesson = Lesson::get();
        $attendance = Attendance::find($id);
        return view('attendances.editattendance', compact('student', 'lesson', 'attendance'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAttendanceRequest  $request
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $messages = [
            'date.required' => 'Date is required!',
            'student.required'   => 'Student is required!',
            'lesson.required' => 'Lesson is required!',
        ];

        // Validate the request
        $this->validate($request, [
            'date'  =>'required',
            'student' =>'required',
            'lesson'   =>'required'

        ], $messages);

        $post = $request->All();

        $student_id = havenUtils::studentID($post['student']);
        $lesson_id = havenUtils::lessonID($post['lesson']);

        $attendance = Attendance::find($post['attendance_id']);

        $attendance->student_id = $student_id;
        $attendance->attendance_date = $post['date'];
        $attendance->lesson_id = $lesson_id;
        $attendance->entered_by = Auth::user()->name;

        $attendance->save();

        Alert::toast('Attendance updated!', 'success');
        return redirect('/attendances');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Attendance::find($id)->delete();

        $message ="Attendance deleted";

        Alert::toast('Attendance deleted!', 'success');
        return redirect()->back();
    }

    public function autocompletestudentSearch(Request $request)
    {
        $datas = Student::select("fname", "mname", "sname")
            ->where("fname","LIKE","%{$request->student}%")
            ->orWhere("mname","LIKE","%{$request->student}%")
            ->orWhere("sname","LIKE","%{$request->student}%")
            ->get();

        $dataModified = array();

        foreach ($datas as $data){
           $dataModified[] = $data->fname.' '.$data->mname.' '.$data->sname;
         }

        return response()->json($dataModified);
    }

    public function attendanceCount($student_id){
        $attendanceCount = Attendance::where('student_id', $student_id)->count();
        return $attendanceCount;
    }
}
