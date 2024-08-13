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
        $timeStart = Carbon::createFromTimeString($this->setting->attendance_time_start);
        $timeStop = Carbon::createFromTimeString($this->setting->attendance_time_stop);
        $now = Carbon::now();

        if (!$now->between($timeStart, $timeStop)) {
            Alert()->error('Attendance can not be entered', 'Attendances can only be entered from ' . $timeStart->format('h:i A') . ' to ' . $timeStop->format('h:i A'));
            return back();
        }

        if ($instructor->hasRole('instructor')) {
            $lesson = Lesson::all();
            $student = Student::find($token);

            if (!$student) {
                Alert()->error('Student not found', 'Choose from the list below or contact the admin');
                return redirect()->route('students');
            }

            if (!$this->attendanceLatest($instructor->instructor_id, $now)) {
                Alert()->error('Attendance can not be entered', 'You can only enter attendances every ' . $this->setting->time_between_attendances . ' minutes, for more information contact the admin!');
                return back();
            }

            if (!havenUtils::checkStudentInstructor($token)) {
                Alert()->error('Student not found', 'Student belongs to '.$student->fleet->car_registration_number.' '. $student->fleet->car_brand_model.' with'.' '.$student->fleet->instructor->fname.' '.$student->fleet->instructor->sname.', scan another document or contact administrator');
                return back();
            }

            $attendanceCount = $student->attendance->count();
            $attendanceThreshold = $this->setting->attendance_threshold;
            $feesBalanceThreshold = $this->setting->fees_balance_threshold;
            $courseDuration = $student->course->duration;
            $feesBalancePercentage = ($student->invoice->invoice_amount_paid / $student->invoice->course_price) * 100;
            $attendancePercentage = ($attendanceCount / $courseDuration) * 100;

            if ($attendancePercentage >= $attendanceThreshold && $feesBalancePercentage < $feesBalanceThreshold) {
                Alert()->error('Fees balance', 'Attendance can not be entered, student has fees balance that must be paid...');
                return back();
            }

            $date = Carbon::now()->timezone('Africa/Blantyre');
            return view('attendances.addattendance', compact('student', 'lesson', 'instructor', 'date'));
        } else {
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
        // Validate the request
        $request->validate([
            'student' => 'required',
            'lesson'  => 'required',
        ], [
            'student.required' => 'Student is required!',
            'lesson.required'  => 'Lesson is required!',
        ]);

        // Decode student name from the request
        $studentName = html_entity_decode($request->input('student'));

        // Retrieve the student by name
        $student = havenUtils::student($studentName);
        if (!$student) {
            return $this->handleError('Oops! Something went wrong', 'Oops! Something wrong happened');
        }

        $student_id = $student->id;

        // Check if the student is enrolled in a course
        $invoice = Invoice::where('student_id', $student_id)->first();
        if (!$invoice) {
            return $this->handleError('Attendance cannot be entered', "{$student->fname} is not enrolled in any course yet!");
        }

        $courseID = $invoice->course_id;
        $courseDuration = havenUtils::courseDuration($courseID);

        // Check if the student has reached the maximum attendance for the course
        if ($this->attendanceCount($student_id) >= $courseDuration) {
            return $this->handleError('Attendance not entered', 'You cannot enter more attendances than the course duration the student is enrolled in!');
        }

        // Check if the lesson is valid
        $lesson_id = havenUtils::lessonID($request->input('lesson'));
        if (!$lesson_id) {
            return $this->handleError('Attendance not entered', 'Invalid lesson selected!');
        }

        // Limit the number of Theory lessons to 10
        if ($request->input('lesson') === 'Theory') {
            $attendanceCount = Attendance::where('lesson_id', $lesson_id)->where('student_id', $student_id)->count();
            if ($attendanceCount >= 10) {
                return $this->handleError('Attendance not entered!', 'You cannot enter more than 10 attendances for Theory lessons for a student.');
            }
        }

        // Create and save the new attendance record
        $attendance = new Attendance([
            'student_id'      => $student_id,
            'attendance_date' => Carbon::now()->tz('Africa/Blantyre'),
            'lesson_id'       => $lesson_id,
            'instructor_id'   => Auth::user()->instructor_id,
        ]);

        if ($attendance->save()) {
            $remainingAttendances = $courseDuration - $this->attendanceCount($student_id);
            $student->status = $remainingAttendances === 0 ? 'Finished' : 'Inprogress';
            $message = $remainingAttendances === 0
                ? "This marks course completion for {$student->fname} {$student->sname}"
                : 'Attendance added successfully!';

            // Send notification SMS
            (new NotificationController)->generalSMS($student, 'Attendance');

            // Save the student's updated status
            $student->save();

            Alert::toast($message, 'success');
        }

        return redirect('/attendances');
    }

    protected function handleError($toastMessage, $alertMessage = null)
    {
        Alert::toast($toastMessage, 'error');
        if ($alertMessage) {
            Alert::error($alertMessage);
        }
        return redirect()->back()->withInput();
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
