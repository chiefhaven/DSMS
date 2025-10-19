<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Instructor;
use App\Models\InstructorPayment;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Student;
use View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Auth;
use Mpdf\Tag\Ins;

class HomeController extends Controller
{
    protected $settings;

    public function __construct()
    {
        $this->middleware('auth');
        $this->settings = Setting::find(1);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'filter' => 'max:255',
        ]);

        $filter = $request->input('filter');
        $time = "today"; // Default to today

        $attendanceCount = 0;

        $instructorEstimatedPay = 0;


        if (Auth::user()->hasRole('instructor')) {
            $attendanceCount = Attendance::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->where('instructor_id', Auth::user()->instructor_id)
                ->count();

            // Get instructor ID
            $instructorId = Auth::user()->instructor_id;

            // Get last payment record
            $lastPayment = InstructorPayment::where('instructor_id', $instructorId)
                ->latest('payment_date')
                ->first();

            // Define date range
            $startDate = $lastPayment ? Carbon::parse($lastPayment->payment_date) : Carbon::now()->startOfMonth();
            $endDate = Carbon::now();

            $attendanceCount = Attendance::where('instructor_id', $instructorId)
                ->whereBetween('created_at', [$startDate, $endDate])->count();

            $instructorEstimatedPay = $attendanceCount * $this->settings->bonus;

        }

        $student = Student::with('Invoice', 'User')
            ->orderBy('created_at', 'DESC')
            ->take(10)
            ->get();

        $invoices = Invoice::with('Student')
            ->orderBy('created_at', 'DESC')
            ->take(10)
            ->get();

        $activities = Activity::orderBy('created_at', 'DESC')->paginate(500);

        $instructors = Instructor::where('status', 'active')
        ->with(['attendances', 'payments' => function ($q) {
            $q->latest('payment_date');
        }])
        ->get();

        $instructors->each(function ($instructor) {
            $lastPayment = $instructor->payments->first(); // latest payment
            $startDate = $lastPayment ? Carbon::parse($lastPayment->payment_date) : $instructor->created_at;

            $instructor->attendances = $instructor->attendances()
                ->whereBetween('created_at', [$startDate, Carbon::now()])
                ->get();
        });

        $settings = Setting::find(1);

        return view('dashboard', compact(['settings', 'attendanceCount', 'instructors', 'activities', 'student', 'time', 'invoices', 'filter', 'instructorEstimatedPay']))
            ->with('title', 'Dashboard');
    }

    public function summaryData()
    {
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        // Initialize the dates collection for the current month
        $dates = collect();
        $currentDate = Carbon::parse($startOfMonth);

        while ($currentDate->lte($endOfMonth)) {
            $dates->put($currentDate->format('Y-m-d'), 0);
            $currentDate->addDay();
        }

        // Get attendance count per date
        $countAttendance = DB::table('attendances')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get schedule count per date
        $countSchedules = DB::table('schedule_lessons')
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('count(*) as count')) // FIXED: Using start_time instead of created_at
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Convert attendance data to associative array
        $arrayOfAttendances = $dates->toArray();
        foreach ($countAttendance as $attendance) {
            $arrayOfAttendances[$attendance->date] = $attendance->count;
        }

        // Convert schedule data to associative array
        $arrayOfSchedules = $dates->toArray();
        foreach ($countSchedules as $schedule) {
            $arrayOfSchedules[$schedule->date] = $schedule->count;
        }

        // Convert associative arrays to simple arrays of objects
        $attendanceMonthlyInfo = collect($arrayOfAttendances)->map(function ($count, $date) {
            return (object) ['date' => $date, 'count' => $count];
        })->values();

        $schedulesMonthlyInfo = collect($arrayOfSchedules)->map(function ($count, $date) {
            return (object) ['date' => $date, 'count' => $count];
        })->values();

        return response()->json([
            'attendances' => $attendanceMonthlyInfo,
            'schedules' => $schedulesMonthlyInfo
        ], 200);
    }


    public function instructorSummaryData()
    {
        // Get the first day of the current month and the last day of the current month
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

        // Initialize the dates collection for the current month
        $dates = collect();
        $currentDate = Carbon::parse($startOfMonth);

        // Populate the collection with dates for the current month and initialize counts to 0
        while ($currentDate->lte($endOfMonth)) {
            $dates->put($currentDate->format('Y-m-d'), 0);
            $currentDate->addDay();
        }

        // Query to get the count of attendances per date for the current month
        $countAttendance = DB::table('attendances')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('instructor_id', Auth::user()->instructor->id)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Create an associative array to store the attendance counts by date
        $arrayOfAttendances = [];

        // Loop through the results to build the array
        foreach ($countAttendance as $attendance) {
            $arrayOfAttendances[$attendance->date] = $attendance->count;
        }

        // Add dates with zero counts to ensure all dates are included
        foreach ($dates as $date => $value) {
            if (!array_key_exists($date, $arrayOfAttendances)) {
                $arrayOfAttendances[$date] = 0;
            }
        }

        // Sort the array by date
        ksort($arrayOfAttendances);

        // Convert the associative array to a simple array of objects
        $attendanceMonthlyInfo = collect($arrayOfAttendances)->map(function ($count, $date) {
            $item = new \stdClass();
            $item->date = $date;
            $item->count = $count;
            return $item;
        })->values();

        // Return the data as JSON
        return response()->json($attendanceMonthlyInfo, 200);
    }
}
