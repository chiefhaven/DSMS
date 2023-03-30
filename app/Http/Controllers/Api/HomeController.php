<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\Student;
use View;

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
