<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\Student;
use View;

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
    public function index()
    {
        $thisMonth = date('m');
        $lastMonth = date("m", strtotime("first day of previous month"));

        $invoice = Invoice::with('Student','User')->where('invoice_balance', '>', 0.00)->orderBy('date_created', 'DESC')->take(13)->get();
        $student = Student::with('Invoice','User')->orderBy('created_at', 'DESC')->take(10)->get();
        $invoiceCount = $invoice->count();

        $studentCountThisMonth = Student::whereMonth('created_at', $thisMonth)->count();
        $invoiceConutThisMonth = Invoice::whereMonth('created_at', $thisMonth)->count();
        $earningsTotalThisMonth = Invoice::whereMonth('created_at', $thisMonth)->sum('invoice_total');
        $invoiceBalances = Invoice::whereMonth('created_at', $thisMonth)->sum('invoice_balance');
        $earningsTotalLastMonth = Invoice::whereMonth('created_at', $lastMonth)->sum('invoice_total');

        if($earningsTotalLastMonth>0 && $earningsTotalThisMonth>0){

            $salesPercentThisMonth = $earningsTotalThisMonth/$earningsTotalLastMonth*100;
        }

        else{

            $salesPercentThisMonth = $earningsTotalThisMonth/1*100;
        }

        // return view::make('dashboard', compact(['invoice', 'student', 'invoiceConutThisMonth', 'studentCountThisMonth', 'salesPercentThisMonth']));
        return view::make('dashboard', compact(['invoice', 'student', 'invoiceBalances', 'invoiceConutThisMonth', 'studentCountThisMonth', 'salesPercentThisMonth', 'earningsTotalThisMonth']));
    }
}
