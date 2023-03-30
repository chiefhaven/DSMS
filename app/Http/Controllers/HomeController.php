<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            $studentCount = Student::whereDate('created_at', Carbon::today())->count();
            $earningsTotal = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_total');
            $invoiceBalances = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_balance');
            $earningsTotal = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_total');
            $time = "today";
        }

        elseif(request('filter') == 'yesterday' ){

            $studentCount = Student::whereMonth('created_at', Carbon::now()->subDay()->day)->count();
            $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->subDay()->day)->sum('invoice_total');
            $invoiceBalances = Invoice::whereMonth('created_at', Carbon::now()->subDay()->day)->sum('invoice_balance');
            $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->subDay()->day)->sum('invoice_total');
            $time = "yesterday";
        }

        elseif(request('filter') == 'thisweek' ){

            $studentCount = Student::whereMonth('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
            $earningsTotal = Invoice::whereMonth('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('invoice_total');
            $invoiceBalances = Invoice::whereMonth('created_at',[Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('invoice_balance');
            $earningsTotal = Invoice::whereMonth('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('invoice_total');
            $time = "thisweek";
        }

        elseif(request('filter') == 'thismonth' ){

            $studentCount = Student::whereMonth('created_at', Carbon::now()->month)->count();
            $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->month)->sum('invoice_total');
            $invoiceBalances = Invoice::whereMonth('created_at', Carbon::now()->month)->sum('invoice_balance');
            $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->month)->sum('invoice_total');
            $time = "thismonth";
        }

        elseif(request('filter') == 'lastmonth' ){

            $studentCount = Student::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();
            $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->subMonth()->month)->sum('invoice_total');
            $invoiceBalances = Invoice::whereMonth('created_at', Carbon::now()->subMonth()->month)->sum('invoice_balance');
            $earningsTotal = Invoice::whereMonth('created_at', Carbon::now()->subMonth()->month)->sum('invoice_total');
            $time = "lastmonth";
        }

        elseif(request('filter') == 'thisyear' ){

            $studentCount = Student::whereYear('created_at', Carbon::now()->year)->count();
            $earningsTotal = Invoice::whereYear('created_at', Carbon::now()->year)->sum('invoice_total');
            $invoiceBalances = Invoice::whereYear('created_at', Carbon::now()->year)->sum('invoice_balance');
            $earningsTotal = Invoice::whereYear('created_at', Carbon::now()->year)->sum('invoice_total');
            $time = "thisyear";
        }
        elseif(request('filter') == 'alltime' ){

            $studentCount = Student::count();
            $earningsTotal = Invoice::sum('invoice_total');
            $invoiceBalances = Invoice::sum('invoice_balance');
            $earningsTotal = Invoice::sum('invoice_total');
            $time = "alltime";
        }

        elseif(request('filter') == 'lastyear' ){

            $studentCount = Student::whereYear('created_at', Carbon::now()->subYear()->year)->count();
            $earningsTotal = Invoice::whereYear('created_at', Carbon::now()->subYear()->year)->sum('invoice_total');
            $invoiceBalances = Invoice::whereYear('created_at', Carbon::now()->subYear()->year)->sum('invoice_balance');
            $earningsTotal = Invoice::whereYear('created_at', Carbon::now()->subYear()->year)->sum('invoice_total');
            $time = "lastyear";
        }

        else{

            $studentCount = Student::whereDate('created_at', Carbon::today())->count();
            $earningsTotal = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_total');
            $invoiceBalances = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_balance');
            $earningsTotal = Invoice::whereDate('created_at', Carbon::today())->sum('invoice_total');
            $time = "today";
        }

        $lastMonth = date("m", strtotime("first day of previous month"));

        $invoice = Invoice::with('Student','User')->where('invoice_balance', '>', 0.00)->orderBy('date_created', 'DESC')->take(13)->get();
        $student = Student::with('Invoice','User')->orderBy('created_at', 'DESC')->take(15)->get();
        $invoiceCount = $invoice->count();

        return view::make('dashboard', compact(['invoice', 'student', 'invoiceBalances', 'studentCount', 'earningsTotal', 'time']));
    }
}
