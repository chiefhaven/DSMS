<?php

namespace App\Http\Controllers;
use App\Http\Controllers\havenUtils;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Course;
use App\Models\Fleet;
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

/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Auth::user()->hasRole('superAdmin')){
            $invoices = Invoice::with('Student', 'User')->orderBy('created_at', 'DESC')->paginate(10);
            return view('invoices.invoices', compact('invoices'));
        }

        else{
            Alert::toast('You don\'t have permission to access this page', 'warning');
            return redirect('/');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {

        if(Auth::user()->hasRole('superAdmin')){
            $course = Course::get();
            $fleet = Fleet::get();
            $student = Student::find($id);
            return view('invoices.addinvoice', compact('course', 'student', 'fleet'));
        }

        else{
            Alert::toast('You don\'t have permission to enroll a student, Ask the administrator for more information.', 'warning');
            return back();
        }
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
            'student.required' => 'Please choose a Student!',
            'course.required'   => 'Please choose a Course',
            'discount.numeric' => 'Discount must be a number greater than zero',
            'paid_amount.numeric' => 'Amount Paid must be a number',
            'paid_amount.min:0' => 'Amount Paid must be at least zero',
        ];

        // Validate the request
        $this->validate($request, [
            'student'  =>'required',
            'course' =>'required',
            'discount'   =>'numeric|min:0',
            'paid_amount'   =>'numeric|min:0'
        ], $messages);

        $post = $request->All();

        $invoice = new Invoice;

        $discount = (float)$post['discount'];

        if(isset($discount)){

            $discount = $discount;
        }

        else{

            $discount = 0;
        }

        $student_id = havenUtils::student($post['student'])->id;
        //$fleet_id = havenUtils::fleetID($post['fleet']);
        $invoice_total = havenUtils::invoiceDiscountedPrice($post['course'], $discount);
        $invoice_balance = havenUtils::invoiceBalance($post['paid_amount'], $invoice_total);
        $courseId = havenUtils::courseID($post['course']);
        $coursePrice = havenUtils::coursePrice($post['course']);


        if(isset($post['date_created'])){

            $date_created = $post['date_created'];
        }
        else{

            $date_created = date('Y/m/d');
        }


        if(isset($post['inovice_due_date'])){

            $invoice_due_date = $post['invoice_due_date'];
        }
        else{

            $start_date = date('Y-m-d');
            $invoice_due_date = date('Y-m-d', strtotime( $start_date . " +15 days"));

        }

        $invoiceNumber = havenUtils::generateInvoiceNumber();


        $invoice->invoice_number = $invoiceNumber;
        $invoice->student_id = $student_id;
        $invoice->course_id = $courseId;
        $invoice->course_price = $coursePrice;
        $invoice->invoice_total = $invoice_total;
        $invoice->invoice_discount = $discount;
        $invoice->invoice_amount_paid = $post['paid_amount'];
        $invoice->invoice_balance = $invoice_balance;
        $invoice->invoice_payment_due_date   = $invoice_due_date;
        $invoice->invoice_payment_method = 'Cash';
        $invoice->invoice_terms = '';
        $invoice->date_created = $date_created;



        $student = Student::where('id', $student_id)->firstOrFail();
        $student->course_id = $courseId;
        //$student->fleet_id = $fleet_id;


        if(Invoice::where('student_id', '=', $student_id)->count() > 0){
            Alert::toast('There is already an invoice for '.$student->fname.'. Can not be re-enrolled. You must delete the invoice first', 'warning');
        }

        else{
            if($invoice->invoice_amount_paid > 0){

                $payment = new Payment;
                $payment->amount_paid = $invoice->invoice_amount_paid;
                $payment->payment_method_id = 1;
                $payment->transaction_id = Str::random(14);
                $payment->student_id = $student_id;
                $payment->entered_by = Auth::user()->name;

                $invoice->save();
                $student->save();
                $payment->save();
                Alert::toast($student->fname.' successifully enrolled', 'success');
            }

            else{

                $invoice->save();
                $student->save();
                Alert::toast($student->fname.' successifully enrolled', 'success');
            }
        }

        $student = Student::with('User', 'Course', 'Enrollment', 'Invoice', 'Payment')->find($student_id);

        return redirect()->route('viewStudent', ['id' => $student_id]);
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
        return view('invoices.viewinvoice', [ 'invoice' => $invoice ], compact('invoice', 'setting'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $course = Course::get();
        $fleet = Fleet::get();
        $invoice = Invoice::with('User', 'Course', 'Student')->where('invoice_number', $id)->firstOrFail();
        return view('invoices.editinvoice', [ 'invoice' => $invoice ], compact('invoice', 'course', 'fleet'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $messages = [
            'student.required' => 'Please choose a Student!',
            'course.required'   => 'Please choose a Course',
            'discount.numeric' => 'Discount must be a number greater than zero',
        ];

        // Validate the request
        $this->validate($request, [
            'student'  =>'required',
            'course' =>'required',
            'discount'   =>'numeric|min:0'
        ], $messages);

        $post = $request->All();

        $invoice = Invoice::where('invoice_number', $post['invoice_number'])->firstOrFail();

        $discount = (float)$post['discount'];

        if(isset($discount)){

            $discount = $discount;
        }

        else{

            $discount = 0;
        }


        if(isset($post['date_created'])){

            $date_created = $post['date_created'];
        }
        else{

            $date_created = date('Y/m/d');
        }


        if(isset($post['inovice_due_date'])){

            $invoice_due_date = $post['invoice_due_date'];
        }
        else{

            $start_date = date('Y-m-d');
            $invoice_due_date = date('Y-m-d', strtotime( $start_date . " +10 days"));

        }


        $student_id = havenUtils::student($post['student'])->id;
        //$fleet_id = havenUtils::fleetID($post['fleet']);
        $invoice_total = havenUtils::invoiceDiscountedPrice($post['course'], $discount);
        $courseId = havenUtils::courseID($post['course']);
        $coursePrice = havenUtils::coursePrice($post['course']);
        $invoice_balance = havenUtils::invoiceBalance($post['paid_amount'], $invoice_total);

        $invoice->invoice_number = $post['invoice_number'];
        $invoice->student_id = $student_id;
        $invoice->course_id = $courseId;
        $invoice->course_price = $coursePrice;
        $invoice->invoice_total = $invoice_total;
        $invoice->invoice_discount = $discount;
        $invoice->invoice_amount_paid = $post['paid_amount'];
        $invoice->invoice_balance = $invoice_balance;
        $invoice->invoice_payment_due_date = $invoice_due_date;
        $invoice->invoice_terms = '';
        $invoice->date_created = $date_created;


        $student = Student::where('id', $student_id)->firstOrFail();
        $student->course_id = $courseId;

        //$student->fleet_id = $fleet_id;

        $invoice->save();
        $student->save();

        return redirect('/invoices')->with('message', 'Invoice updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $Invoice)
    {
        $student_id = $Invoice->student_id;
        $student = Student::where('id', $student_id)->firstOrFail();
        Payment::destroy('student_id', $student_id);

        try{
            $student->course_id = Null;
            $student->fleet_id = Null;
            $Invoice->delete();
            $student->save();
            Alert::toast('Invoice deleted', 'success');
        }

        catch(Exception $e){
            Alert::toast('Invoice not deleted, somethingwent wrong', 'danger');
        }
        return back();
    }

    public function invoicePDF($id)
    {
        $setting= Setting::with('District')->find(1);
        $date = date('j F, Y');
        $setting = Setting::find(1);
        $invoice = Invoice::with('User', 'Course', 'Student')->where('invoice_number',$id)->firstOrFail();

        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca-9143/'.$id);

        $pdf = PDF::loadView('pdf_templates.invoice_template', compact('setting', 'invoice', 'date', 'setting', 'qrCode'));
        return $pdf->download('Daron Driving School-'.$invoice->student->fname.' '.$invoice->student->sname.' Cash Receipt.pdf');
    }

    public function search(Request $request){

        $invoices = Invoice::with('Student')
            ->where('invoice_number', 'like', '%' . request('search') . '%')
            ->orWhere('date_created', 'like', '%' . request('search') . '%')
            ->orwhereHas('Student', function($q){
                $q->where('fname','like', '%' . request('search') . '%')
                ->orwhere('mname','like', '%' . request('search') . '%')
                ->orwhere('sname','like', '%' . request('search') . '%');})->paginate(10);

        if ($request->has(['field', 'sortOrder']) && $request->field != null) {
            $student->orderBy(request('field'), request('sortOrder'));
        }

        return view('invoices.invoices', compact('invoices'));
    }

    public function invoiceQrCode($id){
        $student = havenUtils::invoiceQrCode($id);
        return view('qrCodeGuest', compact('student'));
    }
}
