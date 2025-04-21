<?php

namespace App\Http\Controllers;

use App\Models\InstructorPayment;
use App\Http\Requests\StoreInstructorPaymentRequest;
use App\Http\Requests\UpdateInstructorPaymentRequest;
use Yajra\DataTables\Facades\DataTables;

class InstructorPaymentController extends Controller
{
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
        //
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
