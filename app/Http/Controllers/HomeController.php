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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display dashboard.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        if ($user->hasRole('student') && !$user->hasRole('instructor') && !$user->can('access management')) {
            return view('dashboard', ['title' => 'Dashboard']);
        }

        $settings = Setting::findOrFail(1);

        $now = Carbon::now();

        $attendanceCount = 0;
        $instructorEstimatedPay = 0;

        /*
        |--------------------------------------------------------------------------
        | Instructor Attendance & Estimated Payment
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('instructor') && $user->instructor_id) {

            $instructor = Instructor::find($user->instructor_id);

            if ($instructor) {

                $lastPaymentDate = InstructorPayment::query()
                    ->where('instructor_id', $instructor->id)
                    ->latest('payment_date')
                    ->value('payment_date');

                $startDate = $lastPaymentDate
                    ? Carbon::parse($lastPaymentDate)
                    : $now->copy()->startOfMonth();

                $attendanceCount = Attendance::query()
                    ->where('instructor_id', $instructor->id)
                    ->whereBetween('created_at', [
                        $startDate,
                        $now,
                    ])
                    ->count();

                $instructorEstimatedPay = $attendanceCount
                    * (float) $settings->bonus;
            }
        }

        if ($user->hasRole('instructor') && !$user->can('access management')) {
            return view('dashboard', [
                'title' => 'Dashboard',
                'attendanceCount' => $attendanceCount,
                'instructorEstimatedPay' => $instructorEstimatedPay,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Recent Students
        |--------------------------------------------------------------------------
        */

        $students = Student::query()
            ->with(['Invoice', 'User'])
            ->latest()
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Invoices
        |--------------------------------------------------------------------------
        */

        $invoices = Invoice::query()
            ->with('Student')
            ->latest()
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Activities
        |--------------------------------------------------------------------------
        */

        $activities = Activity::query()
            ->latest()
            ->paginate(25);

        /*
        |--------------------------------------------------------------------------
        | Active Instructors
        |--------------------------------------------------------------------------
        |
        | Retrieve active instructors and their latest payments.
        | Attendance counts are calculated using database queries
        | instead of loading all attendance records.
        |
        */

        $instructors = Instructor::query()
            ->where('status', 'active')
            ->with([
                'payments' => function ($query) {
                    $query->latest('payment_date');
                },
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Calculate Instructor Attendance
        |--------------------------------------------------------------------------
        */

        $instructors->each(function ($instructor) use ($now, $settings) {

            $lastPayment = $instructor->payments->first();

            $startDate = $lastPayment
                ? Carbon::parse($lastPayment->payment_date)
                : Carbon::parse($instructor->created_at);

            $attendanceCount = Attendance::query()
                ->where('instructor_id', $instructor->id)
                ->whereBetween('created_at', [
                    $startDate,
                    $now,
                ])
                ->count();

            // Attach calculated values to instructor model.
            $instructor->attendance_count = $attendanceCount;

            $instructor->estimated_pay = $attendanceCount
                * (float) $settings->bonus;

            $instructor->last_payment_date = $lastPayment?->payment_date;
        });

        /*
        |--------------------------------------------------------------------------
        | Dashboard View
        |--------------------------------------------------------------------------
        */

        return view('dashboard', [
            'title'                  => 'Dashboard',
            'attendanceCount'        => $attendanceCount,
            'instructorEstimatedPay' => $instructorEstimatedPay,
            'instructors'            => $instructors,
            'activities'             => $activities,
            'students'               => $students,
            'invoices'               => $invoices,
            'filter'                 => $validated['filter'] ?? null,
            'settings'               => $settings,
            'time'                   => 'today',
        ]);
    }

    /**
     * Monthly dashboard attendance and schedules summary.
     */
    public function summaryData()
    {
        [$start, $end] = $this->currentMonthRange();

        $scaffold = $this->buildDateScaffold($start, $end);

        /*
        |--------------------------------------------------------------------------
        | Attendance Summary
        |--------------------------------------------------------------------------
        */

        $attendances = DB::table('attendances')
            ->selectRaw('DATE(created_at) AS date, COUNT(*) AS count')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        /*
        |--------------------------------------------------------------------------
        | Schedule Summary
        |--------------------------------------------------------------------------
        */

        $schedules = DB::table('schedule_lessons')
            ->selectRaw('DATE(start_time) AS date, COUNT(*) AS count')
            ->whereBetween('start_time', [$start, $end])
            ->groupByRaw('DATE(start_time)')
            ->pluck('count', 'date');

        return response()->json([
            'attendances' => $this->mergeIntoScaffold(
                $scaffold,
                $attendances
            ),

            'schedules' => $this->mergeIntoScaffold(
                $scaffold,
                $schedules
            ),
        ]);
    }

    /**
     * Monthly attendance summary for authenticated instructor.
     */
    public function instructorSummaryData()
    {
        $user = Auth::user();

        abort_unless(
            $user->hasRole('instructor') && $user->instructor_id,
            403,
            'Unauthorized access.'
        );

        [$start, $end] = $this->currentMonthRange();

        $scaffold = $this->buildDateScaffold($start, $end);

        $attendances = DB::table('attendances')
            ->selectRaw('DATE(created_at) AS date, COUNT(*) AS count')
            ->where('instructor_id', $user->instructor_id)
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        return response()->json(
            $this->mergeIntoScaffold(
                $scaffold,
                $attendances
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get current month's date range.
     */
    private function currentMonthRange(): array
    {
        $now = Carbon::now();

        return [
            $now->copy()->startOfMonth(),
            $now->copy()->endOfMonth(),
        ];
    }

    /**
     * Generate date scaffold with zero attendance.
     */
    private function buildDateScaffold(
        Carbon $start,
        Carbon $end
    ): Collection {

        return collect(CarbonPeriod::create(
            $start->copy()->startOfDay(),
            $end->copy()->startOfDay()
        ))->mapWithKeys(
            fn ($date) => [
                $date->toDateString() => 0,
            ]
        );
    }

    /**
     * Merge attendance counts into date scaffold.
     */
    private function mergeIntoScaffold(
        Collection $scaffold,
        Collection $counts
    ): Collection {

        return $scaffold
            ->merge($counts)
            ->map(function ($count, $date) {

                return [
                    'date'  => $date,
                    'count' => (int) $count,
                ];

            })
            ->values();
    }
}
