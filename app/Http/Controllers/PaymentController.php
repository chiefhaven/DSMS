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
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:access management')->except(['index', 'fetchPayments', 'store', 'destroy']);
        $this->middleware('permission:view fee payments')->only(['index', 'fetchPayments']);
        $this->middleware('permission:record fee payments')->only('store');
        $this->middleware('permission:delete fee payments')->only('destroy');
    }

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

        $payments = Payment::readableBy(Auth::user())->with(['student', 'paymentMethod'])
            ->orderBy('created_at', 'DESC');

        if ($search) {
            $payments->where(function($query) use ($search) {
                $query->where('transaction_id', 'like', "%$search%")
                    ->orWhereHas('student', function($q) use ($search) {
                         $q->where('fname', 'like', "%$search%")
                            ->orWhere('mname', 'like', "%$search%")
                            ->orWhere('sname', 'like', "%$search%");
                    });
            });
        }

        return DataTables::of($payments)
        ->filter(function () {
            // Search already applied above
        })
        ->addColumn('actions', function ($payment) {
            if (!auth()->user()->can('delete fee payments')) {
                return '';
            }

            $delete = '<form method="POST" action="' . route('payments.destroy', $payment->id) . '" style="display:inline;">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="btn dropdown-item nav-main-link delete-confirm text-danger">
                                        <i class="fa fa-trash me-3"></i> Delete
                                    </button>
                               </form>';

            return '
                <div class="dropdown d-inline-block">
                    <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="dropdown"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>
                    <div class="dropdown-menu dropdown-menu-end">
                        ' . $delete . '
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

            return '<span class="text-title">' . e($fullName ?: '-') . '</span>';
        })
        ->addColumn('payment_method', function ($payment) {
            return $payment->paymentMethod->name ?? '-';
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
        $cashId = DB::table('payment_methods')->where('name', 'Cash')->value('id');

        $validator = Validator::make($request->all(), [
            'invoice_id'     => 'required',
            'payment_method' => ['required', Rule::exists('payment_methods', 'id')],
            'paid_amount'    => 'required|numeric|min:1',
            'transaction_number' => [
                Rule::requiredIf(fn () => $request->payment_method != $cashId),
                'min:1',
            ],
            'payment_proof'  => [
                Rule::requiredIf(fn () => $request->payment_method != $cashId),
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,pdf',
                'max:5120'
            ],
        ], [
            'invoice_id.required'         => 'No invoice is being paid for!',
            'paid_amount.numeric'         => 'Amount Paid must be a number',
            'paid_amount.min'             => 'Amount Paid must be at least one',
            'payment_method.exists'       => 'The selected payment method does not exist.',
            'payment_method.required'     => 'Please select a payment method.',
            'transaction_number.required' => 'Transaction number is required for non-cash payments.',
            'transaction_number.min'      => 'Transaction number must not be empty.',
            'payment_proof.required'      => 'Payment proof is required for non-cash payments.',
            'payment_proof.image'         => 'The payment proof must be an image.',
            'payment_proof.mimes'         => 'The payment proof must be a file of type: jpeg, png, jpg, gif, or pdf.',
            'payment_proof.max'           => 'The payment proof must not be greater than 5MB.',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedPath = null;
        try {
            [$payment, $studentId] = DB::transaction(function () use ($request, &$uploadedPath) {
                $invoice = Invoice::with('student')->whereKey($request->input('invoice_id'))->lockForUpdate()->firstOrFail();
                abort_if(!$invoice->student, 404, 'Student not found for this invoice.');
                abort_if($request->input('paid_amount') > $invoice->invoice_balance, 422, 'Payment amount cannot be more than the remaining balance.');

                $payment = new Payment;
                if ($request->hasFile('payment_proof')) {
                    $filename = Str::uuid() . '.' . $request->file('payment_proof')->extension();
                    $request->file('payment_proof')->move(public_path('media/paymentProof'), $filename);
                    $uploadedPath = public_path('media/paymentProof/' . $filename);
                    $payment->payment_proof = $filename;
                }

                $payment->amount_paid = $request->input('paid_amount');
                $payment->payment_method_id = $request->input('payment_method');
                $payment->transaction_id = Str::random(14);
                $payment->student_id = $invoice->student->id;
                $payment->entered_by = Auth::user()->name;
                $payment->recorded_by_user_id = Auth::id();
                $invoice->invoice_amount_paid += $request->input('paid_amount');
                $invoice->invoice_balance = $invoice->invoice_total - $invoice->invoice_amount_paid;
                $payment->save();
                $invoice->save();

                return [$payment, $invoice->student->id];
            });
        } catch (\Throwable $e) {
            if ($uploadedPath && File::exists($uploadedPath) && !File::delete($uploadedPath)) {
                Log::error('Failed to remove payment proof after payment rollback.', ['path' => $uploadedPath]);
            }
            throw $e;
        }

        // Send SMS notification
        $sms = new NotificationController;
        $sms->balanceSMS($studentId, 'Payment');

        return response()->json([
            'message' => 'Payment added successfully.',
            'transaction_id' => $payment->transaction_id,
        ]);
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
        $payment = DB::transaction(function () use ($id) {
            $payment = Payment::readableBy(Auth::user())->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($invoice = Invoice::where('student_id', $payment->student_id)->lockForUpdate()->first()) {
                $invoice->invoice_balance += $payment->amount_paid;
                $invoice->invoice_amount_paid -= $payment->amount_paid;
                $invoice->save();
            }
            $payment->delete();
            return $payment;
        });

        if ($payment->payment_proof) {
            $filePath = public_path('media/paymentProof/' . $payment->payment_proof);
            if (File::exists($filePath) && !File::delete($filePath)) {
                Log::error('Payment deleted but proof file could not be removed.', ['payment_id' => $payment->id, 'path' => $filePath]);
                Alert::toast('Payment deleted, but its proof file could not be removed.', 'warning');
                return back();
            }
        }

        Alert::toast('Payment deleted successfully.', 'success');
        return back();
    }

}
