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
use Illuminate\Validation\Rule;

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
            'invoice_number.required' => 'No invoice is being paid for!',
            'paid_amount.numeric' => 'Amount Paid must be a number',
            'paid_amount.min' => 'Amount Paid must be at least one',
            'payment_method.exists' => 'Payment method you selected does not exist'
        ];

        // Validate the request
        $rules = [
            'invoice_number'   => 'required',
            'payment_method' => 'required|exists:payment_methods,id',
            'paid_amount'     => [
                'required',
                'numeric',
                'min:1',
            ],
        ];

        $validatedData = $request->validate($rules, $messages);
        $post = $request->all();

        // Fetch invoice once
        $invoice = Invoice::where('invoice_number', $post['invoice_number'])->first();

        if (!$invoice) {
            return back()->withErrors(['invoice_number' => 'Invoice not found.']);
        }

        if ($post['paid_amount'] > $invoice->invoice_balance) {
            Alert::error('Payment not entered', 'Payment amount cannot be more than the remaining balance');
            return back()->with([
                'message' => 'Payment amount cannot be more than the remaining balance!',
                'alert-type' => 'danger'
            ]);
        }

        $payment = new Payment;
        $student = havenUtils::studentID_InvoiceNumber($post['invoice_number']);

        if (!$student || !$student->student) {
            return back()->withErrors(['invoice_number' => 'Invalid invoice number. Student not found.']);
        }

        $invoice_amount_paid = havenUtils::invoicePaid($post['invoice_number'], $post['paid_amount']);
        $invoice_balance = $invoice->invoice_total - $invoice_amount_paid;

        // Handle payment proof file
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = $request->file('payment_proof')->storeAs(
                'paymentProofs',
                time() . '_' . $request->file('payment_proof')->getClientOriginalName(),
                'public'
            );
            $payment->payment_proof = $paymentProofPath;
        }

        // Assign payment details
        $payment->amount_paid = $post['paid_amount'];
        $payment->payment_method_id = $post['payment_method'];
        $payment->transaction_id = Str::random(14);
        $payment->student_id = $student->student->id;
        $payment->entered_by = Auth::user()->name;

        // Update invoice balance
        $invoice->invoice_amount_paid = $invoice_amount_paid;
        $invoice->invoice_balance = $invoice_balance;

        $payment->save();
        $invoice->save();

        // Send SMS notification
        $sms = new NotificationController;
        $sms->balanceSMS($student->student->id, 'Payment');

        Alert::toast('Payment added successfully', 'success');
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
