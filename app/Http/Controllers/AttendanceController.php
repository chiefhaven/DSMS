<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Fleet;
use App\Models\Instructor;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Student;
use App\Notifications\AttendanceAdded;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use RealRashid\SweetAlert\Facades\Alert;
use Yajra\DataTables\Facades\DataTables;
use PDF;

class AttendanceController extends Controller
{
    protected function settings(): Setting
    {
        return Setting::findOrFail(1);
    }

    protected function isInstructor(): bool
    {
        return Auth::user()->hasRole('instructor');
    }

    protected function attendanceQuery()
    {
        return Attendance::query()->when($this->isInstructor(), function ($query) {
            $query->where('instructor_id', Auth::user()->instructor_id);
        });
    }

    public function index()
    {
        $attendance = $this->attendanceQuery()
            ->with(['student', 'lesson'])
            ->orderByDesc('attendance_date')
            ->limit(5000)
            ->get();

        $instructor = $this->isInstructor() ? Auth::user()->instructor : null;

        return view('attendances.attendances', compact('attendance', 'instructor'));
    }

    public function fetchAttendances(Request $request)
    {
        $query = $this->attendanceQuery()
            ->with(['student.invoice', 'lesson', 'administrator', 'instructor'])
            ->orderByDesc('attendance_date');

        return DataTables::eloquent($query)            ->addColumn('student', function (Attendance $attendance) {
                $student = $attendance->student;
                if (!$student) {
                    return '-';
                }

                $balance = 'K' . number_format((float) ($student->invoice->invoice_balance ?? 0));
                $progress = (float) havenUtils::studentProgress($student->id);
                $color = $progress >= 80 ? 'text-success' : ($progress >= 50 ? 'text-warning' : 'text-danger');
                $name = e(trim($student->fname . ' ' . $student->sname));

                return "{$name}<br><span class='small text-muted'>Fees balance: {$balance}</span> - "
                    . "<span class='small {$color} fw-bold'>Progress: {$progress}%</span>";
            })
            ->addColumn('lesson', fn (Attendance $a) => e($a->lesson->name ?? '-'))
            ->addColumn('instructor', function (Attendance $a) {
                if ($a->instructor) {
                    return e(trim($a->instructor->fname . ' ' . $a->instructor->sname));
                }
                if ($a->administrator) {
                    return '<span class="text-danger">' . e(trim($a->administrator->fname . ' ' . $a->administrator->sname)) . ' (bulk)</span>';
                }
                return '-';
            })
            ->editColumn('attendance_date', fn (Attendance $a) => $a->attendance_date?->format('j F, Y, H:i:s') ?? '-')
            ->addColumn('actions', function (Attendance $a) {
                $buttons = '<a href="' . e(url("/viewattendance/{$a->id}")) . '" class="dropdown-item">View</a>';
                if (Auth::user()->hasRole('superAdmin')) {
                    $buttons .= '<form method="POST" action="' . e(url("/editattendance/{$a->id}")) . '">'
                        . csrf_field() . '<button class="dropdown-item" type="submit">Edit</button></form>';
                    $buttons .= '<form method="POST" action="' . e(route('attendances.destroy', $a->id)) . '">'
                        . csrf_field() . method_field('DELETE')
                        . '<button class="dropdown-item text-danger" type="submit" onclick="return confirm(\'Are you sure?\')">Delete</button></form>';
                }
                return '<div class="dropdown"><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>'
                    . '<div class="dropdown-menu dropdown-menu-end">' . $buttons . '</div></div>';
            })
            ->rawColumns(['student', 'instructor', 'actions'])
            ->orderColumn('attendance_date', 'attendance_date $1')
            ->make(true);
    }

    public function create(Request $request, $token)
    {
        $settings = $this->settings();
        $now = Carbon::now('Africa/Blantyre');
        $start = Carbon::parse($settings->attendance_time_start, 'Africa/Blantyre');
        $stop = Carbon::parse($settings->attendance_time_stop, 'Africa/Blantyre');

        if (!$now->betweenIncluded($start, $stop)) {
            Alert::error('Attendance cannot be entered',
                'Attendances can only be entered from ' . $start->format('h:i A') . ' to ' . $stop->format('h:i A'));
            return back();
        }

        if (!$this->isInstructor()) {
            $student = havenUtils::invoiceQrCode($token);
            return view('qrCodeGuest', compact('student'));
        }

        $instructor = Auth::user();
        $profile = $instructor->instructor;
        if (!$profile || !$profile->department) {
            Alert::error('Instructor setup incomplete', 'Contact the administrator to assign your department.');
            return back();
        }

        $student = Student::with([
            'course.lessons', 'attendance.lesson.department', 'invoice',
            'fleet.instructor', 'classroom.instructors',
        ])->find($token);

        if (!$student) {
            Alert::error('Student not found', 'Scan another document or contact the administrator.');
            return back();
        }

        $department = strtolower($profile->department->name);
        if ($department === 'practical') {
            if (!$student->fleet) {
                Alert::error('No vehicle assigned', 'The student has not been assigned to a vehicle.');
                return back();
            }
            if (!havenUtils::checkStudentInstructor($token)) {
                $fleet = $student->fleet;
                $assigned = $fleet->instructor;
                $name = $assigned ? trim($assigned->fname . ' ' . $assigned->sname) : 'an assigned instructor';
                Alert::error('Student assigned elsewhere', "Student belongs to {$fleet->car_registration_number} ({$fleet->car_brand_model}) with {$name}.");
                return back();
            }
            if (!$this->attendanceLatest($profile->id, $now)) {
                Alert::error('Attendance cannot be entered',
                    'You can only enter attendances every ' . $settings->time_between_attendances . ' minutes.');
                return back();
            }
        } elseif ($department === 'theory') {
            if (!$student->classroom) {
                Alert::error('No classroom assigned', 'The student does not belong to any classroom.');
                return back();
            }
            if (!havenUtils::checkClassRoom($token)) {
                $classroom = $student->classroom;
                $names = $classroom->instructors->map(fn ($i) => trim($i->fname . ' ' . $i->sname))->implode(', ');
                Alert::error('Student assigned elsewhere',
                    "Student belongs to {$classroom->name} ({$classroom->location}); instructors: " . ($names ?: 'none assigned'));
                return back();
            }
        } else {
            Alert::error('Invalid department', 'Only theory and practical instructors can record attendance.');
            return back();
        }

        $practical = $student->attendance->filter(
            fn ($a) => strtolower($a->lesson?->department?->name ?? '') === 'practical'
        );
        $attendanceCount = $practical->count();
        $counts = $student->attendance->groupBy('lesson_id')->map->count();
        $lessons = $student->course
            ? $student->course->lessons
                ->filter(fn ($lesson) => (string) $lesson->department_id === (string) $profile->department_id
                    && (int) $lesson->pivot->lesson_quantity > $counts->get($lesson->id, 0))
                ->map(function ($lesson) use ($counts) {
                    $lesson->attended = $counts->get($lesson->id, 0) > 0;
                    return $lesson;
                })->sortBy('pivot.order')->values()
            : collect();

        $studentCourseDuration = (int) havenUtils::courseDuration($student->course_id);
        $invoice = $student->invoice;
        $feesPaidPercentage = $invoice && $invoice->invoice_total > 0
            ? ((float) $invoice->invoice_amount_paid / (float) $invoice->invoice_total) * 100 : 0;
        $attendancePercentage = $studentCourseDuration > 0
            ? ($attendanceCount / $studentCourseDuration) * 100 : 0;

        if ($attendancePercentage >= $settings->attendance_threshold
            && $feesPaidPercentage < $settings->fees_balance_threshold) {
            Alert::error('Fees balance', 'Attendance cannot be entered until the required fees are paid.');
            return back();
        }

        $date = $now;
        return view('attendances.addattendance', compact(
            'student', 'lessons', 'instructor', 'date', 'attendanceCount', 'studentCourseDuration'
        ));
    }

    public function store(StoreAttendanceRequest $request)
    {
        $data = $request->validate([
            'student' => ['required', 'string'],
            'studentId' => ['required'],
            'lesson' => ['required', 'string'],
        ]);

        if (!$this->isInstructor() || !Auth::user()->instructor_id) {
            abort(403, 'Only assigned instructors can record attendance.');
        }

        $studentId = html_entity_decode((string) $data['studentId']);
        $student = Student::with(['invoice', 'course.lessons.department', 'fleet', 'classroom'])
            ->find($studentId);
        if (!$student) {
            Alert::error('Student not found', 'Please select a registered student.');
            return back();
        }

        $lessonId = havenUtils::lessonID($data['lesson']);
        $course = $student->course;
        $lesson = $course?->lessons->first(fn ($l) => (string) $l->id === (string) $lessonId);
        if (!$lesson) {
            Alert::error('Invalid lesson', 'The selected lesson is not part of this student’s course.');
            return back();
        }

        $profile = Auth::user()->instructor;
        if (!$profile || (string) $lesson->department_id !== (string) $profile->department_id) {
            Alert::error('Invalid lesson', 'This lesson is outside your department.');
            return back();
        }

        $department = strtolower($profile->department?->name ?? '');
        if ($department === 'practical') {
            if (!$student->fleet || !havenUtils::checkStudentInstructor($student->id)) {
                Alert::error('Invalid assignment', 'The student is not assigned to your vehicle.');
                return back();
            }
        } elseif ($department === 'theory') {
            if (!$student->classroom || !havenUtils::checkClassRoom($student->id)) {
                Alert::error('Invalid assignment', 'The student is not assigned to your classroom.');
                return back();
            }
        } else {
            abort(403, 'Unsupported instructor department.');
        }

        $settings = $this->settings();
        $now = Carbon::now('Africa/Blantyre');
        $start = Carbon::parse($settings->attendance_time_start, 'Africa/Blantyre');
        $stop = Carbon::parse($settings->attendance_time_stop, 'Africa/Blantyre');
        if (!$now->betweenIncluded($start, $stop)) {
            Alert::error('Attendance closed', 'Attendance entry is outside the allowed hours.');
            return back();
        }

        $result = DB::transaction(function () use ($student, $lesson, $profile, $department, $settings, $now) {
            // Lock the student to serialize attendance submissions for the same student.
            $lockedStudent = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            $total = (int) havenUtils::totalAttendancePerDepartment($student->id);
            $duration = (int) havenUtils::courseDuration($student->course_id);
            if ($duration <= 0 || $total >= $duration) {
                return ['error' => 'You cannot enter more attendances than the course duration.'];
            }

            $lessonCount = Attendance::where('student_id', $student->id)->where('lesson_id', $lesson->id)->count();
            if ($lessonCount >= (int) $lesson->pivot->lesson_quantity) {
                return ['error' => 'The attendance limit for this lesson has been reached.'];
            }

            $duplicate = Attendance::where('student_id', $student->id)
                ->where('lesson_id', $lesson->id)
                ->where('instructor_id', $profile->id)
                ->whereBetween('attendance_date', [$now->copy()->startOfSecond(), $now->copy()->endOfSecond()])
                ->exists();
            if ($duplicate) {
                return ['error' => 'This attendance has already been recorded.'];
            }

            if ($department === 'practical' && !$this->attendanceLatest($profile->id, $now)) {
                return ['error' => 'Please wait ' . $settings->time_between_attendances . ' minutes between attendances.'];
            }

            $practicalCount = Attendance::where('student_id', $student->id)
                ->whereHas('lesson.department', fn ($q) => $q->where('name', 'practical'))->count();
            $invoice = Invoice::where('student_id', $student->id)->first();
            $paid = $invoice && $invoice->invoice_total > 0
                ? (float) $invoice->invoice_amount_paid / (float) $invoice->invoice_total * 100 : 0;
            if ($duration > 0 && $practicalCount / $duration * 100 >= $settings->attendance_threshold
                && $paid < $settings->fees_balance_threshold) {
                return ['error' => 'Student fees must be paid before recording further attendance.'];
            }

            $attendance = new Attendance();
            $attendance->student_id = $student->id;
            $attendance->lesson_id = $lesson->id;
            $attendance->instructor_id = $profile->id;
            $attendance->attendance_date = $now;
            $attendance->save();

            $finished = $total + 1 >= $duration;
            $lockedStudent->status = $finished ? 'Finished' : 'Inprogress';
            if ($finished) {
                $lockedStudent->trainingLevel_id = havenUtils::trainingLevelID('finished');
                $lockedStudent->fleet_id = null;
                $lockedStudent->classroom_id = null;
            }
            $lockedStudent->save();

            return ['attendance' => $attendance, 'finished' => $finished];
        });

        if (isset($result['error'])) {
            Alert::error('Attendance not entered', $result['error']);
            return back();
        }

        // External side effects must not roll back a successfully saved attendance.
        try {
            (new NotificationController())->generalSMS($student, 'Attendance');
            $student->load('user');
            if ($student->user) {
                $name = trim($profile->fname . ' ' . $profile->sname);
                $student->user->notify(new AttendanceAdded($student, $result['attendance'], $name));
            }
        } catch (\Throwable $e) {
            Log::error('Attendance notification failed', [
                'attendance_id' => $result['attendance']->id, 'error' => $e->getMessage(),
            ]);
        }

        Alert::toast($result['finished']
            ? 'Course completed. Student unassigned from vehicle and classroom.'
            : 'Attendance added successfully!', 'success');
        return redirect('/attendances');
    }

    public function show(Attendance $attendance)
    {
        abort(404);
    }

    public function edit($id)
    {
        abort(404);
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        abort_unless(Auth::user()->hasRole('superAdmin'), 403);
        $data = $request->validate([
            'date' => ['required', 'date'],
            'student' => ['required', 'string'],
            'lesson' => ['required', 'string'],
            'attendance_id' => [$attendance->exists ? 'nullable' : 'required'],
        ]);
        $student = havenUtils::student($data['student']);
        if (!$student) {
            Alert::error('Student not found', 'Please select a valid student.');
            return back();
        }
        $record = $attendance->exists ? $attendance : Attendance::findOrFail($data['attendance_id']);
        $record->student_id = $student->id;
        $record->lesson_id = havenUtils::lessonID($data['lesson']);
        $record->attendance_date = Carbon::parse($data['date'], 'Africa/Blantyre');
        $record->save();
        Alert::toast('Attendance updated!', 'success');
        return redirect('/attendances');
    }

    public function destroy($id)
    {
        abort_unless(Auth::user()->hasRole('superAdmin'), 403);
        Attendance::findOrFail($id)->delete();
        Alert::toast('Attendance deleted!', 'success');
        return back();
    }

    public function autocompletestudentSearch(Request $request)
    {
        $data = $request->validate(['search' => ['required', 'string', 'max:50']]);
        try {
            $term = '%' . addcslashes($data['search'], '%_\\') . '%';
            $query = Student::query()->with('user')
                ->where('status', '!=', 'Finished');

            if ($this->isInstructor()) {
                $id = Auth::user()->instructor_id;
                $department = strtolower(Auth::user()->instructor?->department?->name ?? '');
                if ($department === 'practical') {
                    $fleetIds = Fleet::where('instructor_id', $id)->select('id');
                    $query->whereIn('fleet_id', $fleetIds);
                } elseif ($department === 'theory') {
                    $classroomIds = DB::table('classroom_instructor')
                        ->where('instructor_id', $id)->select('classroom_id');
                    $query->whereIn('classroom_id', $classroomIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $students = $query->where(function ($q) use ($term) {
                $q->where('fname', 'like', $term)
                    ->orWhere('mname', 'like', $term)
                    ->orWhere('sname', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('trn', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $term));
            })->orderBy('fname')->limit(50)->get();

            return response()->json($students->map(function ($student) {
                $name = trim(implode(' ', array_filter([$student->fname, $student->mname, $student->sname])));
                return [
                    'id' => $student->id,
                    'text' => $name,
                    'display' => "{$name} (TRN: {$student->trn})",
                    'email' => $student->user?->email,
                    'phone' => $student->phone,
                    'trn' => $student->trn,
                ];
            }));
        } catch (\Throwable $e) {
            Log::error('Student search failed', ['error' => $e->getMessage(), 'user' => Auth::id()]);
            return response()->json(['message' => 'Search failed. Please try again.'], 500);
        }
    }

    public function attendanceCount($student_id)
    {
        return Attendance::where('student_id', $student_id)->count();
    }

    public function attendanceLatest($instructor_token, $now)
    {
        $latest = Attendance::where('instructor_id', $instructor_token)
            ->orderByDesc('attendance_date')->first();
        if (!$latest) {
            return true;
        }
        $minutes = Carbon::parse($latest->attendance_date)->diffInMinutes($now, false);
        return $minutes >= (float) $this->settings()->time_between_attendances;
    }

    public function attendanceSummary(Request $request, $id)
    {
        $instructor = Instructor::find($id);
        if (!$instructor) {
            Alert::error('Error', 'Instructor not found.');
            return back();
        }
        if ($this->isInstructor() && (string) Auth::user()->instructor_id !== (string) $id) {
            abort(403);
        }

        $data = $request->validate([
            'period' => ['required', 'in:today,yesterday,thisweek,lastweek,thismonth,lastmonth,thisyear,lastyear,custom'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $now = Carbon::now('Africa/Blantyre');
        switch ($data['period']) {
            case 'today': $start = $now->copy()->startOfDay(); $end = $now->copy()->endOfDay(); break;
            case 'yesterday': $start = $now->copy()->subDay()->startOfDay(); $end = $now->copy()->subDay()->endOfDay(); break;
            case 'thisweek': $start = $now->copy()->startOfWeek(); $end = $now->copy()->endOfWeek(); break;
            case 'lastweek': $start = $now->copy()->subWeek()->startOfWeek(); $end = $now->copy()->subWeek()->endOfWeek(); break;
            case 'thismonth': $start = $now->copy()->startOfMonth(); $end = $now->copy()->endOfMonth(); break;
            case 'lastmonth': $start = $now->copy()->subMonthNoOverflow()->startOfMonth(); $end = $now->copy()->subMonthNoOverflow()->endOfMonth(); break;
            case 'thisyear': $start = $now->copy()->startOfYear(); $end = $now->copy()->endOfYear(); break;
            case 'lastyear': $start = $now->copy()->subYear()->startOfYear(); $end = $now->copy()->subYear()->endOfYear(); break;
            default:
                $start = Carbon::parse($data['start_date'], 'Africa/Blantyre')->startOfDay();
                $end = Carbon::parse($data['end_date'], 'Africa/Blantyre')->endOfDay();
        }

        $attendances = Attendance::where('instructor_id', $instructor->id)
            ->whereBetween('attendance_date', [$start, $end])
            ->with(['student', 'lesson'])
            ->orderBy('attendance_date')->get();

        if ($attendances->isEmpty()) {
            Alert::error('Empty', 'No attendances found for the selected period.');
            return back();
        }

        $setting = $this->settings();
        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca/' . $instructor->id);
        $pdf = PDF::loadView('pdf_templates.attendanceSummary', compact('instructor', 'setting', 'qrCode', 'attendances'));
        return $pdf->download('Daron Driving School-' . $instructor->fname . ' ' . $instructor->sname . ' Attendance Summary.pdf');
    }
}
