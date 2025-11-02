<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Student;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Fleet;
use App\Models\Instructor;
use App\Models\Setting;
use App\Notifications\AttendanceAdded;
use Auth;
use PDF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use Yajra\DataTables\Facades\DataTables;

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
            ->orderBy('attendance_date', 'DESC')->take(5000)->get();

            $instructor = Auth::user()->instructor;
        }
        else{
            $instructor = null;
            $attendance = Attendance::with('Student', 'Lesson')
            ->orderBy('attendance_date', 'DESC')->take(5000)->get();
        }

        return view('attendances.attendances', compact('attendance', 'instructor'));
    }

    public function fetchAttendances(Request $request)
    {
        // Base query
        $attendances = Attendance::query()->with('student', 'lesson', 'administrator');

        // Only load instructor relation if user is not instructor
        if (!Auth::user()->hasRole('instructor')) {
            $attendances->with('instructor');
        }

        // Filter for instructor role
        if (Auth::user()->hasRole('instructor')) {
            $attendances->where('instructor_id', Auth::user()->instructor_id);
        }

        // Order by latest attendance
        $attendances->orderBy('attendance_date', 'DESC');


        return DataTables::of($attendances)
            ->addColumn('student', function ($attend) {
                return $attend->student
                    ? "{$attend->student->fname} {$attend->student->sname}"
                    : '-';
            })
            ->addColumn('lesson', fn($attend) => $attend->lesson->name ?? '-')
            ->addColumn('instructor', function ($attend) {
                if (isset($attend->instructor)) {
                    return "{$attend->instructor->fname} {$attend->instructor->sname}";
                } elseif (isset($attend->administrator)) {
                    return "{$attend->administrator->fname} {$attend->administrator->sname} (bulk)";
                } else {
                    return '-';
                }
            })
            ->addColumn('attendance_date', fn($attend) => $attend->attendance_date->format('j F, Y, H:i:s'))
            ->addColumn('actions', function ($attend) {
                $buttons = '<a href="' . url("/viewattendance/{$attend->id}") . '" class="dropdown-item">View</a>';

                if (auth()->user()->hasRole('superAdmin')) {
                    $buttons .= '<form method="POST" action="' . url("/editattendance/{$attend->id}") . '" class="d-inline">'
                        . csrf_field()
                        . '<button class="dropdown-item" type="submit">Edit</button>'
                        . '</form>';

                    $buttons .= '<form method="POST" action="' . url("/deleteattendance/{$attend->id}") . '" class="d-inline">'
                        . csrf_field()
                        . method_field('DELETE')
                        . '<button class="dropdown-item text-danger" type="submit" onclick="return confirm(\'Are you sure?\')">Delete</button>'
                        . '</form>';
                }

                return '<div class="dropdown">'
                    . '<button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown">Actions</button>'
                    . '<div class="dropdown-menu dropdown-menu-end">' . $buttons . '</div>'
                    . '</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $token)
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
                $lessonsCount = $student->attendance
                ->groupBy('lesson_id')
                ->map(fn($group) => $group->count());

                // Filter and map lessons
                $lessons = $student->course->lessons
                ->filter(function ($lesson) use ($instructor, $lessonsCount) {
                    // Ensure the lesson belongs to the instructor's department
                    if ($lesson->department_id !== $instructor->instructor->department_id) {
                        return false;
                    }

                    // Get attendance count or default to 0
                    $attendanceCount = $lessonsCount->get($lesson->id, 0);

                    // Include lessons with lesson_quantity > attendanceCount
                    return $lesson->pivot->lesson_quantity > $attendanceCount;
                })
                ->map(function ($lesson) use ($lessonsCount) {
                    // Add an 'attended' flag
                    $lesson->attended = $lessonsCount->has($lesson->id);
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
            $studentCourseDuration = $student->course->duration;

            if ($student->invoice->invoice_total > 0) {
                $feesBalancePercentage = ($student->invoice->invoice_amount_paid / $student->invoice->invoice_total) * 100;
            } else {
                $feesBalancePercentage = 0;
            }

            if ($studentCourseDuration > 0) {
                $attendancePercentage = ($attendanceCount / $studentCourseDuration) * 100;
            } else {
                $attendancePercentage = 0;
            }


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
            'student.required' => 'Student is required!',
            'lesson.required'  => 'Lesson is required!',
        ];

        // Validate input
        $this->validate($request, [
            'student' => 'required',
            'lesson'  => 'required',
        ], $messages);

        $instructor_id = Auth::user()->instructor_id;
        $post = $request->all();

        // Find the student
        $studentName = html_entity_decode($post['student']);
        if (!$studentName) {
            Alert::error('Oops! Something went wrong', 'Student not registered');
            return back();
        }

        $student = havenUtils::student($studentName);
        if (!$student) {
            Alert::error('Oops! Something went wrong', 'Student not found');
            return back();
        }

        $student_id = $student->id;

        // Get student's course
        $invoice = Invoice::where('student_id', $student_id)->first();
        if (!$invoice || !$invoice->course_id) {
            Alert::error('Attendance cannot be entered', $student->fname . ' is not enrolled in any course yet!');
            return back();
        }

        $courseID = $invoice->course_id;
        $studentCourseDuration = havenUtils::courseDuration($courseID);

        // Check attendance limit
        if (self::attendanceCount($student_id) >= $studentCourseDuration) {
            Alert::error('Attendance not entered', 'You cannot enter more attendances than the course duration.');
            return redirect('/attendances');
        }

        // Lesson validation
        $lesson_id = havenUtils::lessonID($post['lesson']);
        if ($post['lesson'] == 'Theory') {
            $attendanceCount = Attendance::where('lesson_id', $lesson_id)
                ->where('student_id', $student_id)
                ->count();

            if ($attendanceCount >= 10) {
                Alert::error('Attendance not entered!', 'You cannot enter more than 10 Theory attendances.');
                return back();
            }
        }

        // Create attendance
        $attendance = new Attendance();
        $attendance->student_id     = $student_id;
        $attendance->lesson_id      = $lesson_id;
        $attendance->instructor_id  = $instructor_id;
        $attendance->attendance_date = Carbon::now()->tz('Africa/Blantyre');

        $now = Carbon::now()->tz('Africa/Blantyre');
        $existingAttendance = Attendance::where('student_id', $student_id)
        ->where('lesson_id', $lesson_id)
        ->where('instructor_id', $instructor_id)
        ->whereBetween('attendance_date', [
            $now->copy()->startOfSecond(),
            $now->copy()->endOfSecond(),
        ])
        ->first();

        if ($existingAttendance) {
            \Log::info('Duplicate Entry', 'Attendance for this student and lesson has already been recorded just now.');
            return redirect('/scanqrcode');
        }

        $attendance->save();

        // Update student status
        if ($studentCourseDuration - self::attendanceCount($student_id) == 0) {
            $student->status = 'Finished';
            $student->trainingLevel_id = havenUtils::trainingLevelID('finished');
            $message = "This marks course and level completion for {$student->fname} {$student->sname}. Student unassigned from your car.";
        } else {
            $student->status = 'Inprogress';
            $message = 'Attendance added successfully!';
        }

        // Send notifications
        $sms = new NotificationController;
        $sms->generalSMS($student, 'Attendance');

        $admin = Instructor::find($instructor_id);
        $adminName = $admin ? ($admin->fname . ' ' . $admin->sname) : 'Instructor';

        if ($student->user) {
            $student->user->notify(new AttendanceAdded($student, $attendance, $adminName));
        }

        $student->save();

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
        $request->validate([
            'search' => 'required|string|max:50'
        ]);

        try {
            $query = Student::with('user')
                ->when(Auth::user()->hasRole('instructor'), function ($query) {
                    $instructorId = Auth::user()->instructor_id;

                    // Get related fleet and classrooms in single queries
                    $fleetId = Fleet::where('instructor_id', $instructorId)->value('id');
                    $classroomIds = DB::table('classroom_instructor')
                        ->where('instructor_id', $instructorId)
                        ->pluck('classroom_id');

                    // Students must belong to EITHER the instructor's fleet OR classrooms
                    $query->where(function($q) use ($fleetId, $classroomIds) {
                        if ($fleetId) {
                            $q->where('fleet_id', $fleetId);
                        }
                        if ($classroomIds->isNotEmpty()) {
                            $q->orWhere('classroom_id', $classroomIds); // Fixed variable reference
                        }

                        // If instructor has neither fleet nor classrooms, return no students
                        if (!$fleetId && $classroomIds->isEmpty()) {
                            $q->whereRaw('1 = 0'); // Force empty result
                        }
                    });
                })
                ->where(function ($query) use ($request) {
                    $searchTerm = '%' . addcslashes($request->search, '%_\\') . '%'; // Escape special characters
                    $query->where(function($q) use ($searchTerm) {
                        $q->where('fname', 'like', $searchTerm)
                        ->orWhere('mname', 'like', $searchTerm)
                        ->orWhere('sname', 'like', $searchTerm)
                        ->orWhere('phone', 'like', $searchTerm)
                        ->orWhere('trn', 'like', $searchTerm);
                    })->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('email', 'like', $searchTerm);
                    });
                })
                ->orderBy('fname');

            $students = $query->limit(50)->get(); // Added limit for performance

            return response()->json(
                $students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'text' => trim(implode(' ', array_filter([
                            $student->fname,
                            $student->mname,
                            $student->sname
                        ]))),
                        'display' => sprintf('%s (TRN: %s)',
                            trim(implode(' ', array_filter([
                                $student->fname,
                                $student->mname,
                                $student->sname
                            ]))),
                            $student->trn
                        ),
                        'email' => optional($student->user)->email,
                        'phone' => $student->phone,
                        'trn' => $student->trn
                    ];
                })
            );

        } catch (\Exception $e) {
            Log::error('Student search failed', [
                'error' => $e->getMessage(),
                'search' => $request->search,
                'user' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Search failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
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

    public function attendanceSummary(Request $request, $id)
    {
        $instructor = Instructor::find($id);

        if (!$instructor) {
            Alert::error('Error', 'Instructor not found.');
            return back();
        }

        $period = $request->input('period');
        $query = Attendance::where('instructor_id', $instructor->id);

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;

            case 'yesterday':
                $query->whereDate('created_at', Carbon::yesterday());
                break;

            case 'thisweek':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;

            case 'lastweek':
                $query->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                break;

            case 'thismonth':
                $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
                break;

            case 'lastmonth':
                $query->whereMonth('created_at', Carbon::now()->subMonth()->month)->whereYear('created_at', Carbon::now()->subMonth()->year);
                break;

            case 'thisyear':
                $query->whereYear('created_at', Carbon::now()->year);
                break;

            case 'lastyear':
                $query->whereYear('created_at', Carbon::now()->subYear()->year);
                break;

            case 'custom':
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');

                if (!$startDate || !$endDate) {
                    Alert::error('Invalid Date', 'Please select both start and end dates.');
                    return back();
                }

                // Ensure dates are valid
                try {
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();

                    if ($start->greaterThan($end)) {
                        Alert::error('Invalid Date Range', 'Start date cannot be after the end date.');
                        return back();
                    }

                    $query->whereBetween('created_at', [$start, $end]);
                } catch (\Exception $e) {
                    Alert::error('Invalid Date Format', 'Please enter valid dates.');
                    return back();
                }
                break;

            default:
                Alert::error('No selection made', 'Please make a valid selection.');
                return back();
        }

        $attendances = $query->get();

        if ($attendances->isEmpty()) {
            Alert::error('Empty', 'No attendances found for the selected period.');
            return back();
        }

        $setting = $this->setting;
        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca/' . $instructor->id);

        $pdf = PDF::loadView('pdf_templates.attendanceSummary', compact('instructor', 'setting', 'qrCode', 'attendances'));

        return $pdf->download('Daron Driving School-' . $instructor->fname . ' ' . $instructor->sname . ' Attendance Summary.pdf');
    }
}
