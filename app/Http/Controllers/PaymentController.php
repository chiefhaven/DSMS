<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Auth;
use RealRashid\SweetAlert\Facades\Alert;
use PDF;
use App\Models\Invoice;
use App\Models\Course;
use App\Models\Student;
use App\Notifications\Expense;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use BD;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('payments.payments');
    }

    public function fetchPayments(Request $request): JsonResponse
    {
        // Capture the search keyword from the request if provided
        $search = $request->input('search.value'); // This is the global search input

        $payments = Payment::with('Student')
        ->when(Auth::user()->hasRole('admin'), function ($query) {
            $query->where('added_by', Auth::user()->administrator_id);
        })
        ->orderBy('created_at', 'DESC');

        if ($search) {
            $payments->where(function($query) use ($search) {
                $query->where('transaction_id', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%")
                    ->orWhereHas('students', function($q) use ($search) {
                         $q->where('fname', 'like', "%$search%");
                    });
            });
        }

        return DataTables::of($payments)
        ->addColumn('actions', function ($payment) {
            $download = '';
            $edit = '';
            $delete = '';
            $review = '';

            if (auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                if ($payment->approved != true) {
                    $edit = '<a class="dropdown-item nav-main-link btn" href="' . url('#', $payment->id) . '">
                                <i class="fa fa-pencil me-3"></i> Edit
                            </a>';
                }

                if (auth()->user()->hasRole('superAdmin')) {
                    $review = '<a class="dropdown-item nav-main-link btn" href="' . url('#', $payment->id) . '">
                                    <i class="fa fa-eye me-3"></i> View
                                </a>';
                }

                if (auth()->user()->hasRole('superAdmin')) {
                    $delete = '<form method="POST" action="' . url('delete-payment', $payment->id) . '" style="display:inline;">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="btn dropdown-item nav-main-link delete-confirm text-danger">
                                        <i class="fa fa-trash me-3"></i> Delete
                                    </button>
                               </form>';
                }
            }

            return '
                <div class="dropdown d-inline-block">
                    <button class="btn btn-primary" data-bs-toggle="dropdown">Actions</button>
                    <div class="dropdown-menu dropdown-menu-end">
                        ' . $download . $review . $edit . $delete . '
                    </div>
                </div>
            ';
        })
        ->addColumn('transaction_id', function ($payment) {
            return $payment->transaction_id ?? '-';
        })
        ->addColumn('student', function ($payment) {
            // Check if student relationship exists
            if (!$payment->student) {
                return '-';
            }

            // Build name parts, handling empty middle names
            $nameParts = [
                $payment->student->fname ?? '',
                $payment->student->mname ?? '',
                $payment->student->sname ?? ''
            ];

            // Filter out empty parts and join with spaces
            $fullName = implode(' ', array_filter($nameParts));

            return '<span class="text-title">' . ($fullName ?: '-') . '</span>';
        })
        ->addColumn('payment_method', function ($payment) {
            return $payment->payment_method ?? 'Cash'; // Default to 'Cash' if not specified
        })
        ->addColumn('amount', function ($payment) {
            return '<div class="text-end">
                        <strong>K' . number_format($payment->amount_paid, 2) . '</strong>
                    </div>';
        })
        ->addColumn('entered_by', function ($payment) {
            return $payment->entered_by;
        })
        ->addColumn('date', function ($payment) {
            return $payment->created_at ? $payment->created_at->format('j F, Y') : '-';
        })
        ->addColumn('payment_proof', function ($payment) {
            if ($payment->payment_proof) {
                $imagePath = 'media/paymentProof/' . $payment->payment_proof;
                return '
                    <div class="text-center">
                        <a href="'.asset($imagePath).'"
                            target="_blank"
                            class="">
                            <img src="'.asset($imagePath).'"
                                width="100px"
                                alt="Proof of Payment"
                                onerror="this.onerror=null;this.src=\''.asset('media/default-image.png').'\'">
                        </a>
                    </div>
                ';
            }
            return 'No proof uploaded';
        })
        ->rawColumns(['actions', 'amount', 'payment_proof','student'])
        ->make(true);
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
            'payment_proof' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
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
        $invoice = havenUtils::studentID_InvoiceNumber($post['invoice_number']);

        if (!$invoice || !$invoice->student) {
            return back()->withErrors(['invoice_number' => 'Invalid invoice number. Student not found.']);
        }

        $invoice_amount_paid = havenUtils::invoicePaid($post['invoice_number'], $post['paid_amount']);
        $invoice_balance = $invoice->invoice_total - $invoice_amount_paid;

        // Handle payment proof file
        if ($request->hasFile('payment_proof')) {
            // Generate a unique filename
            $filename = time() . '_' . $request->file('payment_proof')->getClientOriginalName();

            // Store the file in public/media/paymentProof/
            $request->file('payment_proof')->move(public_path('media/paymentProof'), $filename);

            // Store just the filename in the database
            $payment->payment_proof = $filename;
        }

        // Assign payment details
        $payment->amount_paid = $post['paid_amount'];
        $payment->payment_method_id = $post['payment_method'];
        $payment->transaction_id = Str::random(14);
        $payment->student_id = $invoice->student->id;
        $payment->entered_by = Auth::user()->name;

        // Update invoice balance
        $invoice->invoice_amount_paid = $invoice_amount_paid;
        $invoice->invoice_balance = $invoice_balance;

        $payment->save();
        $invoice->save();

        // Send SMS notification
        $sms = new NotificationController;
        $sms->balanceSMS($invoice->student->id, 'Payment');

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
        try {
            // Authorization check
            if (!Auth::user()->hasRole('superAdmin')) {
                throw new \Exception('You do not have permission to delete payments.');
            }

            // Find payment with proper exception handling
            $payment = Payment::findOrFail($id);

            // Begin database transaction
            DB::beginTransaction();

            // Update invoice balance if exists
            if ($invoice = Invoice::where('student_id', $payment->student_id)->first()) {
                $invoice->invoice_balance += $payment->amount_paid;
                $invoice->invoice_amount_paid -= $payment->amount_paid;

                if (!$invoice->save()) {
                    throw new \Exception('Failed to update invoice balance.');
                }
            }

            // Delete payment proof file if exists
            if ($payment->payment_proof) {
                $filePath = public_path('media/paymentProof/' . $payment->payment_proof);

                if (File::exists($filePath)) {
                    if (!File::delete($filePath)) {
                        throw new \Exception('Failed to delete payment proof file.');
                    }
                }
            }

            // Delete payment record
            if (!$payment->delete()) {
                throw new \Exception('Failed to delete payment record.');
            }

            // Commit transaction if all operations succeeded
            DB::commit();

            Alert::toast('Payment deleted successfully.', 'success');
            return back();

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Alert::toast('Payment not found.', 'error');
            return back();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment deletion failed: ' . $e->getMessage());
            Alert::toast($e->getMessage() ?: 'An error occurred while deleting the payment.', 'error');
            return back();
        }
    }

}
