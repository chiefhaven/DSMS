<?php

namespace App\Http\Controllers;

use App\Models\InstructorPayment;
use App\Http\Requests\StoreInstructorPaymentRequest;
use App\Http\Requests\UpdateInstructorPaymentRequest;
use App\Models\Instructor;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Auth;

class InstructorPaymentController extends Controller
{
    protected $bonus;

    public function __construct() {
        $this->bonus = Setting::find(1) ? Setting::find(1)->bonus : null;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('instructors.payments');
    }

    public function fetchPayments()
    {
        $payments = InstructorPayment::with('instructor')
            ->orderByDesc('payment_date')
            ->get();

            return DataTables::of($payments)
            ->addColumn('actions', function ($payment) {
                // View Action
                $view = '<a class="dropdown-item" href="' . url('/viewpayment', $payment->id) . '">
                            <i class="fa fa-eye"></i> View
                        </a>';

                // Edit Action
                $edit = '<a class="dropdown-item" href="' . url('/editpayment', $payment->id) . '">
                            <i class="fa fa-pencil-alt"></i> Edit
                        </a>';

                // Delete Action
                $delete = '<form method="POST" action="' . url('delete-payment', $payment->id) . '" style="display:inline;">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="dropdown-item delete-confirm">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </form>';

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $view . $edit . $delete . '
                        </div>
                    </div>
                ';
            })
            ->addColumn('instructor', function ($payment) {
                return $payment->instructor->fname . ' ' . $payment->instructor->sname;
            })
            ->addColumn('payment_month', function ($payment) {
                return \Carbon\Carbon::createFromFormat('Y-m', $payment->payment_month)->format('F Y');
            })
            ->addColumn('attendances', function ($payment) {
                return $payment->attendances_count;
            })
            ->addColumn('per_attendance', function ($payment) {
                return 'K' . number_format($payment->pay_per_attendance, 2);
            })
            ->addColumn('total', function ($payment) {
                return 'K' . number_format($payment->total_payment, 2);
            })
            ->addColumn('payment_date', function ($payment) {
                return $payment->payment_date->format('d M, Y');
            })
            ->addColumn('status', function ($payment) {
                return ucfirst($payment->status);
            })
            ->rawColumns(['actions', 'instructor', 'payment_month', 'total', 'payment_date', 'status'])
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
     * @param  \App\Http\Requests\StoreInstructorPaymentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInstructorPaymentRequest $request)
    {
        // Check permission
        if (!Auth::user()->hasRole('superAdmin')) {
            return response()->json([
                'message' => 'You are not allowed to make payments to instructors.'
            ], 403); // 403 is more appropriate for "Forbidden"
        }

        $todaysDate = Carbon::now();

        if ($todaysDate->day <= 25) {
            return response()->json([
                'message' => 'Payments can only be processed after the 25th of every month.'
            ], 409);
        }

        // Check if payments have already been made this month
        $paymentsThisMonth = InstructorPayment::whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', $todaysDate->month)
            ->exists(); // More efficient than get() + count()

        if ($paymentsThisMonth) {
            return response()->json([
                'message' => 'Payments already done for this month, can only be made once a month.'
            ], 409); // 409 Conflict is more semantically appropriate
        }

        $instructors = Instructor::where('status', 'active')->get();

        foreach ($instructors as $instructor) {
            $bonusThisMonth = $instructor->attendances()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

            $instructorPayment = new InstructorPayment;
            $instructorPayment->instructor_id = $instructor->id;
            $instructorPayment->attendances_count = $bonusThisMonth;
            $instructorPayment->pay_per_attendance = $this->bonus;
            $instructorPayment->status = 'Paid';
            $instructorPayment->total_payment = $bonusThisMonth * $this->bonus;
            try {
                $paymentDate = Carbon::now()->format('d-m-Y');
                $instructorPayment->payment_date = $paymentDate;
            } catch (\Exception $e) {
                // Handle the exception (e.g., invalid date format)
                Log::error('Invalid date format: ' . $e->getMessage());
                // Optionally, set a default or return error
            }

            try {
                $paymentMonth = Carbon::now()->format('Y-m');
                $instructorPayment->payment_month = $paymentMonth;
            } catch (\Exception $e) {
                Log::error('Invalid month format: ' . $e->getMessage());
            }
            $instructorPayment->save();
        }

        return response()->json([
            'message' => 'Early bonus payment processed successfully.'
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InstructorPayment  $instructorPayment
     * @return \Illuminate\Http\Response
     */
    public function show(InstructorPayment $instructorPayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\InstructorPayment  $instructorPayment
     * @return \Illuminate\Http\Response
     */
    public function edit(InstructorPayment $instructorPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInstructorPaymentRequest  $request
     * @param  \App\Models\InstructorPayment  $instructorPayment
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInstructorPaymentRequest $request, InstructorPayment $instructorPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InstructorPayment  $instructorPayment
     * @return \Illuminate\Http\Response
     */
    public function destroy(InstructorPayment $instructorPayment)
    {
        //
    }
}
