<?php

namespace App\Http\Controllers;

use App\Models\ExpensePayment;
use App\Http\Requests\StoreexpensePaymentRequest;
use App\Http\Requests\UpdateexpensePaymentRequest;
use App\Models\ExpenseTypeOption;
use App\Models\Student;
use App\Notifications\ExpensePaymentMade;
use Auth;
use DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class ExpensePaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Capture the search keyword from the request if provided
        $search = $request->input('search.value');

        // Base query with eager loading
        $expensePayments = ExpensePayment::with([
            'paymentUser.administrator',
            'student',
            'expense.students',
            'Expense' => fn ($q) => $q->with('expenseTypeOption')
        ]);

        // Load mapping of expense type option names
        $expenseTypeOptions = ExpenseTypeOption::with('expenseType')->get();
        $optionToTypeName = $expenseTypeOptions->mapWithKeys(fn ($option) => [
            $option->id => $option?->name ?? '-',
        ]);

        // Apply search filter if a search term is present
        if ($search) {
            $expensePayments->where(function($q) use ($search) {
                $q->whereHas('expense', function ($q2) use ($search) {
                    $q2->where('group', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%");
                })
                ->orWhereHas('student', function ($q3) use ($search) {
                    $q3->where('fname', 'like', "%$search%")
                    ->orWhere('sname', 'like', "%$search%");
                })
                ->orWhere('payment_method', 'like', "%$search%")
                ->orWhere('amount', 'like', "%$search%");
            });
        }

        return DataTables::eloquent($expensePayments)
            ->addColumn('student', fn ($payment) =>
                $payment->student
                    ? trim(
                        $payment->student->fname . ' ' .
                        ($payment->student->mname ?? '') . ' ' .
                        $payment->student->sname
                    )
                    : '-'
            )
            ->addColumn('group', fn ($payment) =>
                $payment->expense ? $payment->expense->group : '-'
            )
            ->addColumn('expense_type', function ($payment) use ($optionToTypeName) {
                $student = $payment->student;
                $matchedPivot = $payment->expense?->students->firstWhere('id', $student?->id);
                return $matchedPivot && $matchedPivot->pivot->expense_type
                    ? ($optionToTypeName[$matchedPivot->pivot->expense_type] ?? '-')
                    : '-';
            })
            ->addColumn('amount', fn ($payment) =>
                '<div class="text-end"><strong>K' . number_format($payment->amount, 2) . '</strong></div>'
            )
            ->addColumn('payment_method', fn ($payment) =>
                $payment->payment_method ?? '-'
            )
            ->addColumn('date_paid', fn ($payment) =>
                $payment->payment_date
                    ? \Carbon\Carbon::parse($payment->payment_date)->format('j F, Y')
                    : '-'
            )
            ->addColumn('paid_by', function ($payment) {
                return optional($payment->paymentUser->administrator)
                    ? $payment->paymentUser->administrator->fname . ' ' . $payment->paymentUser->administrator->sname
                    : '-';
            })
            ->addColumn('actions', function ($payment) {
                $receipt = '';
                $delete = '';

                if (auth()->user()->hasAnyRole(['superAdmin', 'financeAdmin'])) {
                    $receipt = '<form method="GET" action="' . url('expense-payment-receipt', $payment->id) . '">
                        <button class="dropdown-item nav-main-link btn download-confirm" type="submit">
                            <i class="fa fa-download me-3"></i> Receipt
                        </button>
                    </form>';

                    if (auth()->user()->hasRole('superAdmin')) {
                        $delete = '<a href="javascript:void(0);" class="dropdown-item nav-main-link btn" onclick="deletePayment(\'' . $payment->id . '\')">
                                        <i class="fa fa-trash me-3"></i> Delete
                                    </a>';
                    }
                }

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $receipt . $delete . '
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['amount', 'actions'])
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
     * @param  \App\Http\Requests\StoreexpensePaymentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreexpensePaymentRequest $request, $studentId, $expenseId)
    {
        if (!Auth::user()->hasRole(['superAdmin', 'financeAdmin'])) {
            Log::warning('Unauthorized payment attempt by user ID: ' . Auth::id());
            return response()->json([
                'message' => 'You do not have permission to make a payment.'
            ], 403);
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'in:Cash,Bank Transfer,Mobile Money'],
        ]);

        $student = Student::findOrFail($studentId);
        Log::info("Processing payment for student ID: {$studentId}, expense ID: {$expenseId} by user ID: " . Auth::id());

        DB::transaction(function () use ($request, $student, $expenseId) {

            $expense = $student->expenses()
                ->where('expenses.id', $expenseId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$expense->approved) {
                Log::warning("Attempted payment on unapproved expense ID: {$expenseId}");
                abort(403, 'Expense is not approved yet.');
            }

            if ($expense->pivot->status == 1) {
                Log::info("Payment already completed for expense ID: {$expenseId} by student ID: {$student->id}");
                abort(422, 'Student has already paid for this expense.');
            }

            if ($expense->pivot->repeat == 1) {
                Log::info("Attempted payment on repeat-locked expense ID: {$expenseId} for student ID: {$student->id}");
                abort(422, 'Student is repeating and cannot not be paid.');
            }

            if ($request->amount > $expense->pivot->balance) {
                Log::info("Overpayment attempt: Requested amount {$request->amount} exceeds balance {$expense->pivot->balance}");
                abort(422, 'Payment amount must be equal or less to the remaining expense balance.');
            }

            $expensePayment = new ExpensePayment();
            $expensePayment->student_id = $student->id;
            $expensePayment->expense_id = $expenseId;
            $expensePayment->amount = $request->amount;
            $expensePayment->payment_method = $request->payment_method;
            $expensePayment->is_paid = 1;
            $expensePayment->payment_entered_by = auth()->id();
            $expensePayment->payment_date = now();
            $expensePayment->save();

            $expenseBalance = $expense->pivot->balance - $request->amount;
            $expenseStatus = $expenseBalance <= 0 ? 1 : 0;

            $student->expenses()->updateExistingPivot($expenseId, [
                'balance' => $expenseBalance,
                'paid' => $expense->pivot->paid + $request->amount,
                'status' => $expenseStatus,
            ]);

            Log::info("Payment of {$request->amount} recorded for student ID: {$student->id} | Expense ID: {$expenseId} | Balance: {$expenseBalance}");

            $updatedExpense = $student->expenses()->where('expenses.id', $expenseId)->first();

            if ($student->user) {
                $student->user->notify(new ExpensePaymentMade($student, $updatedExpense, $expensePayment));
                Log::info("Notification sent to student user ID: {$student->user->id}");
            }
        });

        return response()->json([
            'message' => 'Payment recorded successfully.'
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\expensePayment  $expensePayment
     * @return \Illuminate\Http\Response
     */
    public function show(expensePayment $expensePayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\expensePayment  $expensePayment
     * @return \Illuminate\Http\Response
     */
    public function edit(expensePayment $expensePayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateexpensePaymentRequest  $request
     * @param  \App\Models\expensePayment  $expensePayment
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateexpensePaymentRequest $request, expensePayment $expensePayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\expensePayment  $expensePayment
     * @return \Illuminate\Http\Response
     */
    public function destroy($expensePayment)
    {
        if (!auth()->user()->hasAnyRole(['superAdmin'])) {
            abort(403, 'Unauthorized.');
        }

        try {
            DB::beginTransaction();

            // Find the payment
            $payment = ExpensePayment::findOrFail($expensePayment);

            // Backup values before deletion
            $amount = $payment->amount;
            $studentId = $payment->student_id;
            $expenseId = $payment->expense_id;

            // Delete the payment
            $payment->delete();

            // Find the matching expense_student record
            $expenseStudent = DB::table('expense_student')
                ->where('student_id', $studentId)
                ->where('expense_id', $expenseId)
                ->first();

            if ($expenseStudent) {
                // Update the values
                DB::table('expense_student')
                    ->where('id', $expenseStudent->id)
                    ->update([
                        'paid' => max(0, $expenseStudent->paid - $amount),
                        'balance' => $expenseStudent->balance + $amount,
                        'status' => $expenseStudent->balance + $amount > 0 ? 0 : 1,
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();

            return response()->json(['message' => 'Payment deleted successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment delete failed: ' . $e->getMessage());

            report($e);
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }


    // public function deleteExpensePayment($id)
    // {
    //     if (!auth()->user()->hasAnyRole(['superAdmin', 'admin', 'instructor', 'student'])) {
    //         abort(403, 'Unauthorized.');
    //     }

    //     try {
    //         DB::beginTransaction();

    //         // Find the payment
    //         $payment = ExpensePayment::findOrFail($id);

    //         // Check if it’s already deleted or approved if needed
    //         if (!$payment->status) {
    //             return response()->json(['error' => 'Payment is already deleted.'], 409);
    //         }

    //         // Update payment status
    //         $payment->status = false;
    //         $payment->save();

    //         // Optionally: adjust balances or related models
    //         if ($payment->amount) {
    //             $payment->amount -= $payment->amount;
    //             $payment->save();
    //         }

    //         DB::commit();

    //         return response()->json(['message' => 'Payment deleted successfully.'], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         report($e);
    //         return response()->json(['error' => 'Something went wrong.'], 500);
    //     }
    // }
}
