<?php
namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Expense;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Student;
use View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
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

            default:
                $time = "today";
                $studentCount = Student::whereDate('created_at', Carbon::today())->count();
                $attendanceCount = Attendance::whereDate('created_at', Carbon::today())->count();
                $earningsTotal = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_total');
                $invoiceBalances = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_balance');
                $expensesTotal = Expense::whereDate('created_at', Carbon::today())->sum('approved_amount');
                break;
        }

        $invoice = Invoice::with('Student', 'User')
            ->where('invoice_balance', '>', 0.00)
            ->orderBy('date_created', 'DESC')
            ->take(13)
            ->get();

        $student = Student::with('Invoice', 'User')
            ->orderBy('created_at', 'DESC')
            ->take(15)
            ->get();

        $invoiceCount = $invoice->count();

        return View::make('dashboard', compact(['attendanceCount', 'expensesTotal', 'invoice', 'student', 'invoiceBalances', 'studentCount', 'earningsTotal', 'time']));
    }

    public function summaryData()
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
