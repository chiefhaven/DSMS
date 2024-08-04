<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Lesson;
use App\Models\Student;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Setting;
use Auth;
use PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class AttendanceController extends Controller
{
    protected $setting;

    protected $user;

    public function __construct()
    {
        // Fetch the Site Settings object
        $this->setting = Setting::find(1);
    }
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
        $instructor = Auth::user();
        $timeStart = $this->setting->attendance_time_start;
        $timeStop = $this->setting->attendance_time_stop;
        $now = Carbon::now();
        $start = Carbon::createFromTimeString($timeStart);
        $end = Carbon::createFromTimeString($timeStop);
        if (!$now->between($start, $end)) {
            Alert()->error('Attendance can not be entered','Attendances can only be entered from '.$timeStart->format('h:i A').' to '.$timeStop->format('h:i A'));
            return back();
        }

        if(Auth::user() && Auth::user()->hasRole('instructor')){
            $lesson = Lesson::get();
            $student = Student::find($token);
            if(!isset($student)){
                Alert()->error('Student not found, choose from the list below else contact the admin');
                return redirect()->route('students');
            }

            if($this->attendanceLatest($instructor->instructor_id, $now) == false){
                Alert()->error('Attendance can not be entered','You can only enter attendances every '.$this->setting->time_between_attendances.' minutes, for more information contact the admin!');
                return back();
            };

            $checkStudentInstructor = havenUtils::checkStudentInstructor($token);

             if($checkStudentInstructor == false){
                 Alert()->error('Student not found', 'Student belongs to another car, scan another document or contact administrator');
                 return back();
             }

            if($student->attendance->count()/$student->course->duration*100 >= $this->setting->attendance_threshold && $student->invoice->invoice_balance/$student->invoice->course_price*100 < $this->setting->fees_balance_threshold){
                Alert()->error('Fees balance', 'Attendance can not be entered, student has fees balance that must be paid...');
                return back();
            }

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
        $student_id = havenUtils::student($post['student'])->id;

        //Check course days and compare with attendance
        $courseID = Invoice::where('student_id', $student_id)->firstOrFail()->course_id;

        $student = Student::find($student_id);

        if(!isset($courseID)){
            Alert()->error('Attendance can not be entered', $student->fname.' not enrolled to any course yet!');
        }

        $courseDuration = havenUtils::courseDuration($courseID);

        if(self::attendanceCount($student_id) >= $courseDuration){
            Alert()->error('Attendance not entered','You can not enter more attendances than course duration a student enrolled!');
            return redirect('/attendances');
        }

        $lesson_id = havenUtils::lessonID($post['lesson']);

        if($post['lesson'] == 'Theory'){
           $attendanceCount = Attendance::Where('lesson_id', $lesson_id)->Where('student_id', $student_id)->count();
            if($attendanceCount == 10){
                Alert()->error('Attendance not entered!','You can not enter more than 10 attendances for Theory lessons for a student.');
                return back();
            }
        }

        $attendance = new Attendance;

        if(is_null($student_id)){

            Alert::toast('No such student is registered with us!', 'error');
            return redirect()->back()->withInput();
        }

        else{

            $attendance->student_id = $student_id;
        }

        $attendance->attendance_date = Carbon::now()->tz('Africa/Blantyre');
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

            $sms = new NotificationController;
            $sms->generalSMS($student, 'Attendance');

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

        $student_id = havenUtils::student($post['student'])->id;
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

    public function attendanceLatest($instructor_token, $now){
        $attendance = Attendance::where('instructor_id', $instructor_token)->orderBy('attendance_date', 'desc')->first();

        if(is_null($attendance)){
            return true;
        }

        $latestAttendance = $attendance->attendance_date;

        $timeDifference = $now->diffInMinutes($latestAttendance);

        if($timeDifference > $this->setting->time_between_attendances){
            return true;
        }

        return false;
    }

    public function attendanceSummary(request $request)
    {
        $instructor = Auth::user();
        $period = $request['period'];
        switch($period) {
            case 'today':
                $attendances = Attendance::whereDate('created_at', Carbon::today())
                    ->where('instructor_id', $instructor->instructor_id)
                    ->get();
                break;

            case 'yesterday':
                $attendances = Attendance::whereDate('created_at', Carbon::yesterday())
                    ->where('instructor_id', $instructor->instructor_id)
                    ->get();
                break;

            case 'thisweek':
                $startOfWeek = Carbon::now()->startOfWeek();
                $endOfWeek = Carbon::now()->endOfWeek();
                $attendances = Attendance::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->where('instructor_id', $instructor->instructor_id)
                    ->get();
                break;

            case 'lastweek':
                $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
                $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();
                $attendances = Attendance::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
                    ->where('instructor_id', $instructor->instructor_id)
                    ->get();
                break;

            case 'thismonth':
                $currentMonth = Carbon::now()->month;
                $attendances = Attendance::whereMonth('created_at', $currentMonth)
                    ->where('instructor_id', $instructor->instructor_id)
                    ->get();
                break;

            case 'thisyear':
                $currentYear = Carbon::now()->year;
                $attendances = Attendance::whereYear('created_at', $currentYear)
                    ->where('instructor_id', $instructor->instructor_id)
                    ->get();
                break;

            case 'lastyear':
                $lastYear = Carbon::now()->subYear()->year;
                $attendances = Attendance::whereYear('created_at', $lastYear)
                    ->where('instructor_id', $instructor->instructor_id)
                    ->get();
                break;

            default:
                Alert::error('No selection made', 'Make another selection');
                return back();
        }

        $setting = $this->setting;

        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca/' . $instructor->instructor_id);

        if($attendances->count() == 0){
            Alert::error('Empty', 'No attendances for your selection');
            return back();
        }

        $pdf = PDF::loadView('pdf_templates.attendanceSummary', compact('instructor', 'setting', 'qrCode', 'attendances'));
        return $pdf->download('Daron Driving School-' . $instructor->instructor->fname . ' ' . $instructor->instructor->sname . ' Attendance Summary.pdf');
    }

}
