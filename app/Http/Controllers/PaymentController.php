<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Auth;
use RealRashid\SweetAlert\Facades\Alert;
use PDF;
use App\Models\Invoice;
use App\Models\Course;
use App\Models\Student;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $messages = [
            'invoice_number.required' => 'No invoice os being paid for!',
            'paid_amount.numeric' => 'Amount Paid must be a number',
            'paid_amount.min:0' => 'Amount Paid must be at least zero',
        ];

        // Validate the request
        $this->validate($request, [
            'invoice_number'   =>'required',
            'paid_amount'   =>'numeric|min:0'
        ], $messages);

        $post = $request->All();

        if($post['paid_amount'] > 0){

            $payment = new Payment;

            $student_id = havenUtils::studentID_InvoiceNumber($post['invoice_number']);
            $invoice_amount_paid = havenUtils::invoicePaid($post['invoice_number'], $post['paid_amount']);
            $invoice_balance = Invoice::where('invoice_number', $post['invoice_number'])->first()->invoice_total - $invoice_amount_paid;

            //payment reciept processing
            if($request->file('payment_proof')){
                $paymentProofName = time().$request->file('payment_proof')->getClientOriginalName();
                $request->payment_proof->move(public_path('/../media/paymentProof'), $paymentProofName);
                $payment->payment_proof = $paymentProofName;
            }

            //Payment method processing
            $paymentMethod = havenUtils::paymentMethod($post['payment_method']);

            $payment->amount_paid = $post['paid_amount'];
            $payment->payment_method_id = $paymentMethod;
            $payment->transaction_id = Str::random(14);
            $payment->student_id = $student_id;
            $payment->entered_by = Auth::user()->name;


            $invoice = Invoice::where('invoice_number', $post['invoice_number'])->firstOrFail();
            $invoice->invoice_amount_paid = $invoice_amount_paid;
            $invoice->invoice_balance = $invoice_balance;

            $payment->save();
            $invoice->save();

        }

        Alert::toast('Payment added successifuly', 'success');
        return back();

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $studentPayment = Payment::find($id);
        $invoice = Invoice::where('student_id', $studentPayment->student_id)->first();

        $invoice->invoice_balance = $invoice->invoice_balance + $studentPayment->amount_paid;
        $invoice->invoice_amount_paid = $invoice->invoice_amount_paid - $studentPayment->amount_paid;

        $invoice->save();

        Payment::find($id)->delete();


        Alert::toast('Payment deleted', 'success');

        return redirect()->back();
    }
}
