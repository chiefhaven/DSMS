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
use Carbon\Carbon;
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
        if(Auth::user()->hasRole('instructor')){
            $attendance = Attendance::with('Student', 'Lesson')
            ->where('instructor_id', Auth::user()->instructor_id)
            ->orderBy('attendance_date', 'DESC')->paginate(10);
        }
        else{
            $attendance = Attendance::with('Student', 'Lesson')
            ->orderBy('attendance_date', 'DESC')->paginate(10);
        }

        return view('attendances.attendances', compact('attendance'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($token)
    {
        $now = Carbon::now();
        $start = Carbon::createFromTimeString('05:30');
        $end = Carbon::createFromTimeString('17:30')->addDay();

        if (!$now->between($start, $end)) {
            Alert::toast('Attendances can only be entered from 5:30AM to 5:30PM', 'warning');
            return back();
        }

        if(Auth::user() && Auth::user()->hasRole('instructor')){
            $lesson = Lesson::get();
            $student = Student::find($token);
            if(!isset($student)){
                Alert::toast('Student not found, choose from the list below else contact the admin', 'warning');
                return redirect()->route('students');
            }

            havenUtils::checkStudentInstructor($token);

            $instructor = Auth::user();
            $date = Carbon::now()->timezone('Africa/Blantyre');
            return view('attendances.addattendance', compact('student','lesson', 'instructor', 'date'));

        }

        else{
            $student = havenUtils::invoiceQrCode($token);
            return view('qrCodeGuest', compact('student'));
        }
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
            'student.required'   => 'Student is required!',
            'lesson.required' => 'Lesson is required!',
        ];

        // Validate the request
        $this->validate($request, [
            'student' =>'required',
            'lesson'   =>'required',


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

        $lesson_id = havenUtils::lessonID($post['lesson']);

        $attendance = new Attendance;

        if(is_null($student_id)){

            Alert::toast('No such student is registered with us!', 'error');
            return redirect()->back()->withInput();
        }

        else{

            $attendance->student_id = $student_id;
        }

        $attendance->attendance_date = Carbon::now()->timezone('Africa/Blantyre')->toDate();
        $attendance->lesson_id = $lesson_id;
        $attendance->instructor_id = Auth::user()->instructor_id;

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
        abort(404);

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
