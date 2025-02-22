<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Student;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Instructor;
use App\Models\Setting;
use App\Notifications\AttendanceAdded;
use Auth;
use PDF;
use Carbon\Carbon;
use Exception;
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
            ->orderBy('attendance_date', 'DESC')->take(1000)->get();
        }
        else{
            $attendance = Attendance::with('Student', 'Lesson')
            ->orderBy('attendance_date', 'DESC')->take(1000)->get();
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

            // $lessons = Lesson::where('department_id', $instructor->instructor->department_id)->get();
            $student = Student::with(['Course.lessons', 'Attendance'])->find($token);

            if ($student && $student->course) {
                // Group attendance records by lesson_id and count occurrences
                $lessonCounts = $student->attendance
                ->groupBy('lesson_id')
                ->map(fn($group) => $group->count());

                // Filter and map lessons
                $lessons = $student->course->lessons
                ->filter(function ($lesson) use ($instructor, $lessonCounts) {
                    // Ensure the lesson belongs to the instructor's department
                    if ($lesson->department_id !== $instructor->instructor->department_id) {
                        return false;
                    }

                    // Get attendance count or default to 0
                    $attendanceCount = $lessonCounts->get($lesson->id, 0);

                    // Include lessons with lesson_quantity > attendanceCount
                    return $lesson->pivot->lesson_quantity > $attendanceCount;
                })
                ->map(function ($lesson) use ($lessonCounts) {
                    // Add an 'attended' flag
                    $lesson->attended = $lessonCounts->has($lesson->id);
                    return $lesson;
                })
                ->sortBy('pivot.order') // Sort lessons by the order field in the pivot table
                ->values(); // Reset collection keys

            } else {
                $lessons = collect(); // Return an empty collection if student or course is missing
            }


            if (!$student) {
                Alert()->error('Student not found', 'Scan another document or contact the admin');
                return back();
            }

            if (!$student->fleet && !$student->classroom) {
                Alert::error('Not assigned car or classroom yet', 'Choose from the list below or contact the admin');
                return redirect()->back();
            }

            if (isset($instructor->instructor->department) && $instructor->instructor->department->name == 'practical' && !$this->attendanceLatest($instructor->instructor_id, $now)) {
                Alert()->error('Attendance can not be entered', 'You can only enter attendances every ' . $this->setting->time_between_attendances . ' minutes, for more information contact the admin!');
                return back();
            }


            if ($instructor->instructor->department->name == 'theory' && !$student->classroom) {
                Alert()->error('Student not found', 'Student does not belong to any classroom, please scan another document or contact administrator.');
                return back();
            }


            if ($student->fleet && !havenUtils::checkStudentInstructor($token) && isset($instructor->instructor->department) && $instructor->instructor->department->name == 'practical') {
                $fleetDetails = $student->fleet->car_registration_number . ' ' . $student->fleet->car_brand_model;
                $instructorDetails = $student->fleet->instructor->fname . ' ' . $student->fleet->instructor->sname;
                Alert()->error('Student not found', "Student belongs to $fleetDetails with $instructorDetails. Scan another document or contact administrator.");
                return back();
            }

            if ($student->classroom && !havenUtils::checkClassRoom($token) && isset($instructor->instructor->department) && $instructor->instructor->department->name == 'theory') {
                $classroomDetails = $student->classroom->name . ' ' . $student->classroom->location;

                // Get all instructors' full names
                $instructorNames = $student->classroom->instructors->pluck('fname', 'sname')->map(function($fname, $sname) {
                    return $fname . ' ' . $sname;
                })->implode(', '); // Join all instructor names with a comma

                if (empty($instructorNames)) {
                    $instructorNames = 'no instructors assigned';
                }

                Alert()->error('Student not found', "Student belongs to $classroomDetails whose instructors are $instructorNames. Scan another document or contact administrator.");
                return back();
            }


            $attendanceCount = $student->attendance->count();
            $attendanceThreshold = $this->setting->attendance_threshold;
            $feesBalanceThreshold = $this->setting->fees_balance_threshold;
            $courseDuration = $student->course->duration;
            $feesBalancePercentage = ($student->invoice->invoice_amount_paid / $student->invoice->invoice_total) * 100;
            $attendancePercentage = ($attendanceCount / $courseDuration) * 100;

            if ($attendancePercentage >= $attendanceThreshold && $feesBalancePercentage < $feesBalanceThreshold) {
                Alert()->error('Fees balance', 'Attendance can not be entered, student has fees balance that must be paid...');
                return back();
            }

            $date = Carbon::now()->timezone('Africa/Blantyre');
            return view('attendances.addattendance', compact('student', 'lessons', 'instructor', 'date'));
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
        $messages = [
            'student.required'   => 'Student is required!',
            'lesson.required' => 'Lesson is required!',
        ];

        // Validate the request
        $this->validate($request, [
            'student' =>'required',
            'lesson'   =>'required',


        ], $messages);

        $instructor_id = Auth::user()->instructor_id;

        $post = $request->all();

        $studentName = html_entity_decode($post['student']);

        if (isset($studentName)) {
            $student = havenUtils::student($studentName);
            if ($student) {
                $student_id = $student->id;
            } else {
                // Handle case where student is not found
                Alert::error('Oops! Something went wrong');
                return back();
            }
        } else {
            // Handle case where 'student' key is missing
            Alert::error('Oops! Something wrong happened');
            return back();
        }

        //Check course days and compare with attendance
        $courseID = Invoice::where('student_id', $student_id)->firstOrFail()->course_id;

        $student = Student::find($student_id);

        if(!isset($courseID)){
            Alert()->error('Attendance can not be entered', $student->fname.' not enrolled to any course yet!');
        }

        $studentCourseDuration = havenUtils::courseDuration($courseID);

        if(self::attendanceCount($student_id) >= $studentCourseDuration){
            dd(self::attendanceCount($student_id), $studentCourseDuration);
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
        $attendance->instructor_id = $instructor_id;

        $attendance->save();

        if($attendance->save()){

            if($courseDuration-self::attendanceCount($student_id) == 0){
                $student->status = 'Finished';
                $message = 'This marks course completion for '.$student->fname.' '.$student->sname;
            }

            else{
                $student->status = 'Inprogress';
                $message = 'Attendance added successifuly!';
            }

            $sms = new NotificationController;
            $sms->generalSMS($student, 'Attendance');
            $admin = Instructor::find($instructor_id);
            if (!$admin) {
                // Handle the error, e.g., log or throw an exception
                throw new Exception('Administrator not found.');
            }

            $adminName = $admin->fname . ' ' . $admin->sname;

            if (!$student || !$student->user) {
                throw new Exception('Student or associated user not found.');
            }

            $student->user->notify(new AttendanceAdded($student, $attendance, $adminName));


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
