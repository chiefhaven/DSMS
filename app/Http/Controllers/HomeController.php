<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Instructor;
use App\Models\InstructorPayment;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class HomeController extends Controller
{
    protected Setting $settings;

    public function __construct()
    {
        $this->middleware('auth');
        $this->settings = Setting::findOrFail(1);
    }

    public function index(Request $request)
    {
        $request->validate(['filter' => 'max:255']);

        $attendanceCount        = 0;
        $instructorEstimatedPay = 0;

        if (Auth::user()->hasRole('instructor')) {
            $instructorId = Auth::user()->instructor_id;

            $lastPayment = InstructorPayment::where('instructor_id', $instructorId)
                ->latest('payment_date')
                ->first();

            $startDate = $lastPayment
                ? Carbon::parse($lastPayment->payment_date)
                : Carbon::now()->startOfMonth();

            $attendanceCount        = Attendance::where('instructor_id', $instructorId)
                ->whereBetween('created_at', [$startDate, Carbon::now()])
                ->count();

            $instructorEstimatedPay = $attendanceCount * $this->settings->bonus;
        }

        $students = Student::with('Invoice', 'User')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $invoices = Invoice::with('Student')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $activities = Activity::orderByDesc('created_at')->paginate(25);

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

        return view('dashboard')
            ->with('title', 'Dashboard')
            ->with(compact(
                'attendanceCount',
                'instructorEstimatedPay',
                'instructors',
                'activities',
                'students',
                'invoices',
            ))
            ->with('filter', $request->input('filter'))
            ->with('settings', $this->settings)
            ->with('time', 'today');
    }

    public function summaryData()
    {
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        $scaffold = $this->buildDateScaffold($start, $end);

        $attendances = DB::table('attendances')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->pluck('count', 'date');

        $schedules = DB::table('schedule_lessons')
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('count(*) as count'))
            ->whereBetween('start_time', [$start, $end])
            ->groupBy('date')
            ->pluck('count', 'date');

        return response()->json([
            'attendances' => $this->mergeIntoScaffold($scaffold, $attendances),
            'schedules'   => $this->mergeIntoScaffold($scaffold, $schedules),
        ]);
    }

    public function instructorSummaryData()
    {
        abort_unless(Auth::user()->hasRole('instructor'), 403);

        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();

        $scaffold = $this->buildDateScaffold($start, $end);

        $attendances = DB::table('attendances')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$start, $end])
            ->where('instructor_id', Auth::user()->instructor_id)
            ->groupBy('date')
            ->pluck('count', 'date');

        return response()->json(
            $this->mergeIntoScaffold($scaffold, $attendances)
        );
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    private function buildDateScaffold(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        return collect(CarbonPeriod::create($start, $end))
            ->mapWithKeys(fn(Carbon $date) => [$date->toDateString() => 0]);
    }

    private function mergeIntoScaffold(
        \Illuminate\Support\Collection $scaffold,
        \Illuminate\Support\Collection $counts
    ): \Illuminate\Support\Collection {
        return $scaffold
            ->merge($counts)
            ->map(fn($count, $date) => (object) ['date' => $date, 'count' => $count])
            ->values();
    }
}
