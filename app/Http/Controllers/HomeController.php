<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\expense;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Student;
use View;
use Carbon\Carbon;

class HomeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {

        $validated = $request->validate([
            'filter' => 'max:255',
        ]);

        if(request('filter') == 'today' ){

            $attendanceCount = Attendance::whereDate('created_at', Carbon::today())->count();
            $studentCount = Student::whereDate('created_at', Carbon::today())->count();
            $earningsTotal = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_total');
            $invoiceBalances = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_balance');
            $earningsTotal = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_total');
            $expensesTotal = Expense::whereDate('created_at', Carbon::today())->sum('approved_amount');
            $time = "today";
        }

        elseif(request('filter') == 'yesterday' ){

            $studentCount = Student::whereMonth('created_at', Carbon::now()->subDay()->day)->count();
            $attendanceCount = Attendance::whereMonth('created_at', Carbon::now()->subDay()->day)->count();
            $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->subDay()->day)->sum('invoice_total');
            $invoiceBalances = Invoice::whereMonth('created_at', Carbon::now()->subDay()->day)->sum('invoice_balance');
            $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->subDay()->day)->sum('invoice_total');
            $expensesTotal = Expense::whereMonth('created_at', Carbon::now()->subDay()->day)->sum('approved_amount');
            $time = "yesterday";
        }

        elseif(request('filter') == 'thisweek' ){

            // Calculate the start and end of the current week
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();

            // Count of students created this week
            $studentCount = Student::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();

            // Count of attendances created this week
            $attendanceCount = Attendance::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();

            // Total earnings from invoices created this week
            $earningsTotal = Invoice::whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('invoice_total');

            // Total invoice balances for invoices created this week
            $invoiceBalances = Invoice::whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('invoice_balance');

            // Total expenses approved this week
            $expensesTotal = Expense::whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('approved_amount');

            // Time frame description
            $time = "thisweek";
        }

        elseif(request('filter') == 'thismonth' ){

            // Get the current month
            $currentMonth = Carbon::now()->month;

            // Count of students created this month
            $studentCount = Student::whereMonth('created_at', $currentMonth)->count();

            // Count of attendances created this month
            $attendanceCount = Attendance::whereMonth('created_at', $currentMonth)->count();

            // Total earnings from invoices created this month
            $earningsTotal = Invoice::whereMonth('created_at', $currentMonth)->sum('invoice_total');

            // Total invoice balances for invoices created this month
            $invoiceBalances = Invoice::whereMonth('created_at', $currentMonth)->sum('invoice_balance');

            // Total expenses approved this month
            $expensesTotal = Expense::whereMonth('created_at', $currentMonth)->sum('approved_amount');

            // Time frame description
            $time = "thismonth";
        }

        elseif(request('filter') == 'lastmonth' ){

            // Get the previous month
            $lastMonth = Carbon::now()->subMonth()->month;

            // Count of students created last month
            $studentCount = Student::whereMonth('created_at', $lastMonth)->count();

            // Count of attendances created last month
            $attendanceCount = Attendance::whereMonth('created_at', $lastMonth)->count();

            // Total earnings from invoices created last month
            $earningsTotal = Invoice::whereMonth('created_at', $lastMonth)->sum('invoice_total');

            // Total invoice balances for invoices created last month
            $invoiceBalances = Invoice::whereMonth('created_at', $lastMonth)->sum('invoice_balance');

            // Total expenses approved this month
            $expensesTotal = Expense::whereMonth('created_at', $lastMonth)->sum('approved_amount');

            // Time frame description
            $time = "lastmonth";
        }

        elseif(request('filter') == 'thisyear' ){

            // Get the current year
            $currentYear = Carbon::now()->year;

            // Count of students created this year
            $studentCount = Student::whereYear('created_at', $currentYear)->count();

            // Count of attendances created this year
            $attendanceCount = Attendance::whereYear('created_at', $currentYear)->count();

            // Total earnings from invoices created this year
            $earningsTotal = Invoice::whereYear('created_at', $currentYear)->sum('invoice_total');

            // Total invoice balances for invoices created this year
            $invoiceBalances = Invoice::whereYear('created_at', $currentYear)->sum('invoice_balance');

            // Total expenses approved this month
            $expensesTotal = Expense::whereYear('created_at', $currentYear)->sum('approved_amount');

            // Time frame description
            $time = "thisyear";
        }
        elseif(request('filter') == 'alltime' ){

            // Count of all students
            $studentCount = Student::count();

            // Total earnings from all invoices
            $earningsTotal = Invoice::sum('invoice_total');

            // Total invoice balances from all invoices
            $invoiceBalances = Invoice::sum('invoice_balance');

            // Total invoice balances from all invoices
            $attendanceCount = Attendance::count();

            // Total expenses approved this month
            $expensesTotal = Expense::sum('approved_amount');

            // Time frame description
            $time = "alltime";
        }

        elseif(request('filter') == 'lastyear' ){

            // Get the previous year
            $lastYear = Carbon::now()->subYear()->year;

            // Count of attendances created last year
            $attendanceCount = Attendance::whereYear('created_at', $lastYear)->count();

            // Count of students created last year
            $studentCount = Student::whereYear('created_at', $lastYear)->count();

            // Total earnings from invoices created last year
            $earningsTotal = Invoice::whereYear('created_at', $lastYear)->sum('invoice_total');

            // Total invoice balances for invoices created last year
            $invoiceBalances = Invoice::whereYear('created_at', $lastYear)->sum('invoice_balance');

            // Total expenses approved this month
            $expensesTotal = Expense::whereYear('created_at', $lastYear)->sum('approved_amount');

            // Time frame description
            $time = "lastyear";
        }

        else{

            // Get today's date
            $today = Carbon::today();

            // Count of students created today
            $studentCount = Student::whereDate('created_at', $today)->count();

            // Count of attendances created today
            $attendanceCount = Attendance::whereDate('created_at', $today)->count();

            // Total earnings from invoices created today
            $earningsTotal = Invoice::whereDate('created_at', $today)->sum('invoice_total');

            // Total invoice balances for invoices created today
            $invoiceBalances = Invoice::whereDate('created_at', $today)->sum('invoice_balance');

            // Total expenses approved today
            $expensesTotal = Expense::whereDate('created_at', $today)->sum('approved_amount');

            // Time frame description
            $time = "today";
        }

        // Get the previous month
        $lastMonth = date("m", strtotime("first day of previous month"));

        // Retrieve invoices with a balance greater than 0, along with associated Student and User, ordered by date_created, limited to 13
        $invoice = Invoice::with('Student', 'User')
            ->where('invoice_balance', '>', 0.00)
            ->orderBy('date_created', 'DESC')
            ->take(13)
            ->get();

        // Retrieve students with associated Invoice and User, ordered by created_at, limited to 15
        $student = Student::with('Invoice', 'User')
            ->orderBy('created_at', 'DESC')
            ->take(15)
            ->get();

        // Count the retrieved invoices
        $invoiceCount = $invoice->count();

        return view::make('dashboard', compact(['attendanceCount','expensesTotal','invoice', 'student', 'invoiceBalances', 'studentCount', 'earningsTotal', 'time']));
    }
}
