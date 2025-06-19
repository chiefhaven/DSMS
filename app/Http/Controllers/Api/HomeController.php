<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Expense; //capitals
use App\Models\ExpensePayment;
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
        // 1️⃣ === Monthly daily attendance & schedules ===
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Generate all dates in the month
        $dates = collect();
        $current = $startOfMonth->copy();
        while ($current->lte($endOfMonth)) {
            $dates->put($current->format('Y-m-d'), 0);
            $current->addDay();
        }

        // Attendance counts per day
        $attendanceCounts = DB::table('attendances')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->get();

        // Schedule counts per day
        $scheduleCounts = DB::table('schedule_lessons')
            ->selectRaw('DATE(start_time) as date, COUNT(*) as count')
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->get();

        // Fill arrays
        $attendancePerDay = $dates->toArray();
        foreach ($attendanceCounts as $a) {
            $attendancePerDay[$a->date] = $a->count;
        }

        $schedulePerDay = $dates->toArray();
        foreach ($scheduleCounts as $s) {
            $schedulePerDay[$s->date] = $s->count;
        }

        // Convert to arrays of objects for JSON
        $attendanceMonthlyInfo = collect($attendancePerDay)->map(fn($c, $d) => (object)[
            'date' => $d,
            'count' => $c
        ])->values();

        $schedulesMonthlyInfo = collect($schedulePerDay)->map(fn($c, $d) => (object)[
            'date' => $d,
            'count' => $c
        ])->values();

        // 2️⃣ === Validate filter ===
        $request->validate([
            'filter' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        $filter = $request->input('filter', 'today');

        // 3️⃣ === Determine start & end ===
        switch ($filter) {
            case 'today':
                $start = Carbon::today();
                $end = $start->copy()->endOfDay();
                break;
            case 'yesterday':
                $start = Carbon::yesterday();
                $end = $start->copy()->endOfDay();
                break;
            case 'thisweek':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;
            case 'thismonth':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            case 'lastmonth':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'thisyear':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            case 'lastyear':
                $start = Carbon::now()->subYear()->startOfYear();
                $end = Carbon::now()->subYear()->endOfYear();
                break;
            case 'custom':
                $request->validate([
                    'start_date' => 'required|date|before_or_equal:end_date',
                    'end_date' => 'required|date|after_or_equal:start_date',
                ]);
                $start = Carbon::parse($request->input('start_date'))->startOfDay();
                $end = Carbon::parse($request->input('end_date'))->endOfDay();
                break;
            case 'alltime':
                $start = null;
                $end = null;
                break;
            default:
                $start = Carbon::today();
                $end = $start->copy()->endOfDay();
                break;
        }

        // 4️⃣ === Run stats queries ===
        if ($start && $end) {
            $studentCount = Student::whereBetween('created_at', [$start, $end])->count();
            $attendanceCount = Attendance::whereBetween('created_at', [$start, $end])->count();
            $earningsTotal = Invoice::whereBetween('created_at', [$start, $end])->sum('invoice_total');
            $invoiceBalances = Invoice::whereBetween('created_at', [$start, $end])->sum('invoice_balance');
            $expensesTotal = Expense::whereBetween('updated_at', [$start, $end])->sum('approved_amount');
            $expensesPayments = ExpensePayment::whereBetween('updated_at', [$start, $end])->sum('amount');
        } else {
            $studentCount = Student::count();
            $attendanceCount = Attendance::count();
            $earningsTotal = Invoice::sum('invoice_total');
            $invoiceBalances = Invoice::sum('invoice_balance');
            $expensesTotal = Expense::sum('approved_amount');
            $expensesPayments = ExpensePayment::sum('amount');
        }

        // 5️⃣ === If instructor, override attendanceCount ===
        if (Auth::user()->hasRole('instructor')) {
            $attendanceCount = Attendance::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('instructor_id', Auth::user()->instructor_id)
                ->count();
        }

        // 6️⃣ === Recent invoices with outstanding balance ===
        $invoice = Invoice::with(['student', 'user'])
            ->where('invoice_balance', '>', 0)
            ->orderBy('date_created', 'DESC')
            ->take(13)
            ->get();

        // 7️⃣ === Return all ===
        return response()->json([
            'time' => $filter,
            'studentCount' => $studentCount,
            'attendanceCount' => $attendanceCount,
            'earningsTotal' => $earningsTotal,
            'invoiceBalances' => $invoiceBalances,
            'expensesTotal' => $expensesTotal,
            'expensesPayments' => $expensesPayments,
            'attendances' => $attendanceMonthlyInfo,
            'schedules' => $schedulesMonthlyInfo,
            'recentInvoices' => $invoice,
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
