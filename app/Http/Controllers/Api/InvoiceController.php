<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\havenUtils;
use App\Models\Invoice;
use App\Models\Course;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\Setting;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Illuminate\Support\Str;
use Auth;
use RealRashid\SweetAlert\Facades\Alert;
use PDF;

class InvoiceController extends Controller
{
    public function __construct()
    {

        $this->middleware(['role:superAdmin'], ['role:admin']);

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $invoice = Invoice::with('Student','User')->where('invoice_balance', '>', 0.00)->orderBy('date_created', 'DESC')->take(15)->get();

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
        $setting= Setting::with('District')->find(1);
        $invoice = Invoice::with('User', 'Course', 'Student')->where('invoice_number',$id)->firstOrFail();
        // return view('invoices.viewinvoice', [ 'invoice' => $invoice ], compact('invoice', 'setting'));

        return response()->json($invoice);
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
