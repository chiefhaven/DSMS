<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Expense; //capitals
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Auth;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $thisMonth = date('m');
        $lastMonth = date("m", strtotime("first day of previous month"));

        $invoice = Invoice::with('Student','User')->where('invoice_balance', '>', 0.00)->orderBy('date_created', 'DESC')->take(14)->get();
        $student = Student::with('Invoice','User')->orderBy('created_at', 'DESC')->take(10)->get();
        $invoiceCount = $invoice->count();

        $studentCountThisMonth = Student::whereMonth('created_at', $thisMonth)->count();
        $invoiceConutThisMonth = Invoice::whereMonth('created_at', $thisMonth)->count();
        $earningsTotalThisMonth = Invoice::whereMonth('created_at', $thisMonth)->sum('invoice_total');
        $earningsTotalLastMonth = Invoice::whereMonth('created_at', $thisMonth)->sum('invoice_total');

        if($earningsTotalLastMonth>0 && $earningsTotalThisMonth>0){
            $salesPercentThisMonth = $earningsTotalThisMonth/$earningsTotalLastMonth*100;
        }

        else{

            $salesPercentThisMonth = $earningsTotalThisMonth/1*100;
        }

        return response()->json($invoice); // or use API Resource here

        /* return Response::json(array(
            'invoice' => $invoice,
            'student' => $student,
            'studentCount' => $studentCountThisMonth,
            'salesPercent' => $salesPercentThisMonth,
        )); */
    }

    public function dashboardSummary(Request $request)
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

            $validated = $request->validate([
                'filter' => 'max:255',
            ]);

            $filter = $request->input('filter');
            $time = "today"; // Default to today

            switch ($filter) {
                case 'today':
                    $time = "today";
                    $studentCount = Student::whereDate('created_at', Carbon::today())->count();
                    $attendanceCount = Attendance::whereDate('created_at', Carbon::today())->count();
                    $earningsTotal = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_total');
                    $invoiceBalances = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_balance');
                    $expensesTotal = Expense::whereDate('created_at', Carbon::today())->sum('approved_amount');
                    break;

                case 'yesterday':
                    $time = "yesterday";
                    $studentCount = Student::whereDate('created_at', Carbon::yesterday())->count();
                    $attendanceCount = Attendance::whereDate('created_at', Carbon::yesterday())->count();
                    $earningsTotal = Invoice::whereDate('created_at', Carbon::yesterday())->sum('invoice_total');
                    $invoiceBalances = Invoice::whereDate('created_at', Carbon::yesterday())->sum('invoice_balance');
                    $expensesTotal = Expense::whereDate('created_at', Carbon::yesterday())->sum('approved_amount');
                    break;

                case 'thisweek':
                    $time = "thisweek";
                    $startOfWeek = Carbon::now()->startOfWeek();
                    $endOfWeek = Carbon::now()->endOfWeek();
                    $studentCount = Student::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
                    $attendanceCount = Attendance::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
                    $earningsTotal = Invoice::whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('invoice_total');
                    $invoiceBalances = Invoice::whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('invoice_balance');
                    $expensesTotal = Expense::whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('approved_amount');
                    break;

                case 'thismonth':
                    $time = "thismonth";
                    $studentCount = Student::whereMonth('created_at', Carbon::now()->month)->count();
                    $attendanceCount = Attendance::whereMonth('created_at', Carbon::now()->month)->count();
                    $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->month)->sum('invoice_total');
                    $invoiceBalances = Invoice::whereMonth('created_at', Carbon::now()->month)->sum('invoice_balance');
                    $expensesTotal = Expense::whereMonth('created_at', Carbon::now()->month)->sum('approved_amount');
                    break;

                case 'lastmonth':
                    $time = "lastmonth";
                    $studentCount = Student::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
                    $attendanceCount = Attendance::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
                    $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->subMonth()->month)->sum('invoice_total');
                    $invoiceBalances = Invoice::whereMonth('created_at', Carbon::now()->subMonth()->month)->sum('invoice_balance');
                    $expensesTotal = Expense::whereMonth('created_at', Carbon::now()->subMonth()->month)->sum('approved_amount');
                    break;

                case 'thisyear':
                    $time = "thisyear";
                    $studentCount = Student::whereYear('created_at', Carbon::now()->year)->count();
                    $attendanceCount = Attendance::whereYear('created_at', Carbon::now()->year)->count();
                    $earningsTotal = Invoice::whereYear('created_at', Carbon::now()->year)->sum('invoice_total');
                    $invoiceBalances = Invoice::whereYear('created_at', Carbon::now()->year)->sum('invoice_balance');
                    $expensesTotal = Expense::whereYear('created_at', Carbon::now()->year)->sum('approved_amount');
                    break;

                case 'lastyear':
                    $time = "lastyear";
                    $studentCount = Student::whereYear('created_at', Carbon::now()->subYear()->year)->count();
                    $attendanceCount = Attendance::whereYear('created_at', Carbon::now()->subYear()->year)->count();
                    $earningsTotal = Invoice::whereYear('created_at', Carbon::now()->subYear()->year)->sum('invoice_total');
                    $invoiceBalances = Invoice::whereYear('created_at', Carbon::now()->subYear()->year)->sum('invoice_balance');
                    $expensesTotal = Expense::whereYear('created_at', Carbon::now()->subYear()->year)->sum('approved_amount');
                    break;

                case 'alltime':
                    $time = "alltime";
                    $studentCount = Student::count();
                    $attendanceCount = Attendance::count();
                    $earningsTotal = Invoice::sum('invoice_total');
                    $invoiceBalances = Invoice::sum('invoice_balance');
                    $expensesTotal = Expense::sum('approved_amount');
                    break;

                case 'custom':

                    $request->validate([
                        'start_date' => 'required|date|before_or_equal:end_date',
                        'end_date' => 'required|date|after_or_equal:start_date',
                    ]);

                    $time = "custom";

                    $startDate = request()->input('start_date'); // format: Y-m-d
                    $endDate = request()->input('end_date');     // format: Y-m-d

                    $studentCount = Student::whereBetween('created_at', [$startDate, $endDate])->count();
                    $attendanceCount = Attendance::whereBetween('created_at', [$startDate, $endDate])->count();
                    $earningsTotal = Invoice::whereBetween('created_at', [$startDate, $endDate])->sum('invoice_total');
                    $invoiceBalances = Invoice::whereBetween('created_at', [$startDate, $endDate])->sum('invoice_balance');
                    $expensesTotal = Expense::whereBetween('created_at', [$startDate, $endDate])->sum('approved_amount');
                    break;

                default:
                    $time = "today";
                    $studentCount = Student::whereDate('created_at', Carbon::today())->count();
                    $attendanceCount = Attendance::whereDate('created_at', Carbon::today())->count();
                    $earningsTotal = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_total');
                    $invoiceBalances = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_balance');
                    $expensesTotal = Expense::whereDate('created_at', Carbon::today())->sum('approved_amount');
                    break;
            }

            if (Auth::user()->hasRole('instructor')) {
                $attendanceCount = Attendance::whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->where('instructor_id', Auth::user()->instructor_id)
                    ->count();
            }

            $invoice = Invoice::with('Student', 'User')
                ->where('invoice_balance', '>', 0.00)
                ->orderBy('date_created', 'DESC')
                ->take(13)
                ->get();

        return response()->json([
            'studentCount' => $studentCount,
            'attendanceCount' => $attendanceCount,
            'earningsTotal' => $earningsTotal,
            'invoiceBalances' => $invoiceBalances,
            'expensesTotal' => $expensesTotal,
            'attendances' => $attendanceMonthlyInfo,
            'schedules' => $schedulesMonthlyInfo
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
