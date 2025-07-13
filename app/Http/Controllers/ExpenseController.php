<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Http\Requests\StoreexpenseRequest;
use App\Http\Requests\UpdateexpenseRequest;
use App\Models\Administrator;
use App\Models\ExpensePayment;
use App\Models\ExpenseType;
use App\Models\ExpenseTypeOption;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ExpenseApproved;
use App\Notifications\ExpenseCreated;
use App\Notifications\ExpensePaymentMade;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;
use PDF;
use DB;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class ExpenseController extends Controller
{
    protected $setting;

    public function __construct()
    {
        $this->middleware(['role:superAdmin|admin|financeAdmin']);
        $this->setting = Setting::find(1);
    }/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('expenses.expenses');
    }

    public function fetchExpenses(Request $request): JsonResponse
    {
        // Capture the search keyword from the request if provided
        $search = $request->input('search.value'); // This is the global search input

        $expenses = Expense::with('Students')
        ->when(Auth::user()->hasRole('admin'), function ($query) {
            $query->where('added_by', Auth::user()->administrator_id);
        })
        ->orderBy('created_at', 'DESC');

        if ($search) {
            $expenses->where(function($query) use ($search) {
                $query->where('group', 'like', "%$search%")
                    ->orWhere('expense_type', 'like', "%$search%");
                    // ->orWhereHas('students', function($q) use ($search) {
                    //     $q->where('fname', 'like', "%$search%");
                    // });
            });
        }

        return DataTables::of($expenses)
            ->addColumn('group', function ($expense) {
                return Carbon::createFromFormat('d/m/Y', $expense->group)->format('j F, Y');
            })
            ->addColumn('students', function ($expense) {
                return '<div class="text-center">'.
                            $expense->students->count().
                        '</div>';
            })
            ->addColumn('status', function ($expense) {
                if ($expense->approved == '1') {
                    return '<div class="text-center p-1 text-success">
                                <i class="fa fa-check" aria-hidden="true"></i> Approved
                            </div>';
                } else {
                    return '<div class="text-center p-1 text-danger">
                                <i class="fa fa-times" aria-hidden="true"></i> Unapproved
                            </div>';
                }
            })
            ->addColumn('type', function ($expense) {
                return $expense->group_type ? ExpenseType::find($expense->group_type)?->name : '-';
            })
            ->addColumn('description', function ($expense) {
                return $expense->description;
            })
            ->addColumn('posted_by', function ($expense) {
                return $expense->administrator->fname .' '. $expense->administrator->sname;
            })
            ->addColumn('amount', function ($expense) {
                return '<div class="text-end">
                            <strong>K' . number_format($expense->amount, 2) . '</strong>
                        </div>';
            })
            ->addColumn('approved_by', function ($expense) {
                return Administrator::find($expense->approved_by)?->fname .' '. Administrator::find($expense->approved_by)?->sname ?? '-';
            })
            ->addColumn('date_approved', function ($expense) {
                return $expense->date_approved ? $expense->date_approved->format('j F, Y') : '-';
            })
            ->addColumn('last_edited', function ($expense) {
                $editedBy = 'You';

                if ($expense->edited_by != Auth::user()->administrator->id) {
                    $admin = Administrator::find($expense->edited_by);
                    $editedBy = $admin ? $admin->fname . ' ' . $admin->sname : 'Unknown';
                }

                $date = $expense->updated_at ? $expense->updated_at->format('j F, Y H:i:s') : '-';

                return <<<HTML
                    By: {$editedBy}
                    <div class="sm-text" style="font-size: 12px">
                        {$date}
                    </div>
                HTML;
            })
            ->addColumn('payment_method', function ($expense) {
                return $expense->payment_method ? $expense->payment_method : '-';
            })
            ->addColumn('actions', function ($expense) {
                $download = '';
                $paymentReport = '';
                $edit = '';
                $delete = '';
                $review = '';
                $view = '';

                // Check if user has either role
                if (auth()->user()->hasAnyRole(['superAdmin', 'admin', 'financeAdmin'])) {

                    // Download logic
                    if ($expense->group_type !== '39d3f058-4f04-11f0-aa86-52540066f921') {
                        if ($expense->approved == true) {
                            $download = '<form method="GET" action="' . url('expensedownload', $expense->id) . '">
                                            ' . csrf_field() . '
                                            <button class="dropdown-item nav-main-link btn download-confirm" type="submit">
                                                <i class="fa fa-download me-3"></i> RTD Letter
                                            </button>
                                         </form>';
                        } else {
                            $download = '<p class="dropdown-item text-danger">Download not available</p>';
                        }
                    } else {
                        $download = '<p class="dropdown-item text-success">Go to student profile for TRN reference</p>';
                    }

                    if ($expense->approved == true && auth()->user()->hasAnyRole(['superAdmin', 'financeAdmin', 'admin'])) {
                        $paymentReport = '<form method="GET" action="' . url('expense-payment-report', $expense->id) . '">
                                        ' . csrf_field() . '
                                        <button class="dropdown-item nav-main-link btn download-confirm" type="submit">
                                            <i class="fa fa-download me-3"></i> Payment report
                                        </button>
                                     </form>';
                    } else {
                        $download = '<p class="dropdown-item text-danger">Download not available</p>';
                    }

                    // Edit only allowed if not approved
                    if (auth()->user()->hasAnyRole(['superAdmin', 'admin', 'financeAdmin'])) {
                        $view = '<a class="dropdown-item nav-main-link btn" href="' . url('/view-expense', $expense->id) . '">
                                    <i class="fa fa-eye me-3"></i> View
                                </a>';
                    }

                    // Edit only allowed if not approved
                    if ($expense->approved != true && auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                        $edit = '<a class="dropdown-item nav-main-link btn" href="' . url('/editexpense', $expense->id) . '">
                                    <i class="fa fa-pencil me-3"></i> Edit
                                </a>';
                    }


                    // Only superAdmin can review
                    if (auth()->user()->hasRole('superAdmin')) {
                        $review = '<a class="dropdown-item nav-main-link btn" href="' . url('/review-expense', $expense->id) . '">
                                        <i class="fa fa-magnifying-glass me-3"></i> Review
                                    </a>';
                    }

                    // Only superAdmin can delete if not approved
                    if (auth()->user()->hasRole('superAdmin') && $expense->approved == false) {
                        $delete = '<form method="POST" action="' . url('expenses', $expense->id) . '" style="display:inline;">
                                        ' . csrf_field() . method_field('DELETE') . '
                                        <button type="submit" class="btn dropdown-item nav-main-link delete-confirm">
                                            <i class="fa fa-trash me-3"></i> Delete
                                        </button>
                                   </form>';
                    }
                }

                // Combine and return the full dropdown
                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $download . $paymentReport . $view . $review . $edit . $delete .  '
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['actions', 'last_edited', 'status', 'amount', 'students']) // allow HTML in 'actions'
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('expenses.addexpense');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreexpenseRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreexpenseRequest $request)
    {
        try {
            $messages = [
                'expenseGroupName.required' => 'Expense Group Name is required',
                'students.required' => 'Please select at least one student',
                'students.array' => 'Invalid student data format',
                'students.min' => 'Please select at least one student',
            ];

            $this->validate($request, [
                'expenseGroupName'  => 'required',
                'students'          => 'required|array|min:1'
            ], $messages);
        } catch (\Exception $e) {
            \Log::error('Validation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Validation error.',
                'error' => $e->getMessage()
            ], 422);
        }

        try {
            $post = $request->all();
            $students = $post['students'];

            $user = Auth::user();
            $admin = Administrator::findOrFail($user->administrator_id);
        } catch (\Exception $e) {
            \Log::error('User or admin lookup failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'User authentication or administrator record failed.',
                'error' => $e->getMessage()
            ], 500);
        }

        try {
            $expense = new Expense();
            $expense->group = $post['expenseGroupName'];
            $expense->group_type = $post['expenseGroupType'] ?? null;
            $expense->description = $post['expenseDescription'] ?? null;
            $expense->amount = 0;
            $expense->added_by = $user->administrator_id;
            $expense->save();
        } catch (\Exception $e) {
            \Log::error('Expense creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create expense record.',
                'error' => $e->getMessage()
            ], 500);
        }

        try {
            foreach ($students as $data) {
                $student = havenUtils::student($data['studentName']);
                $student->expenses()->attach($expense->id, [
                    'expense_type' => $data['expenseTypesOption'],
                    'repeat'       => $data['expenses'][0]['pivot']['repeat'] ?? 0,
                    'amount'       => $data['expenseTypesOptionAmount'] ?? 0,
                    'balance'       => $data['expenseTypesOptionAmount'] ?? 0,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Attaching students to expense failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to link students to expense.',
                'error' => $e->getMessage()
            ], 500);
        }

        try {
            $superAdmins = User::role('superAdmin')->get();
            foreach ($superAdmins as $superAdmin) {
                $superAdmin->notify(new ExpenseCreated($expense, $admin->fname));
            }
        } catch (\Exception $e) {
            \Log::error('Notification to super admins failed: ' . $e->getMessage());
            // Continue even if notification fails
        }

        return response()->json(['message' => 'Expense added successfully'], 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function show(expense $expense)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function reviewExpense(expense $expense)
    {
        return view('expenses.reviewExpense', compact('expense'));
    }

    public function reviewExpenseData(expense $expense)
    {
        // Fetch students linked to the given expense ID
        $expenseId = $expense->id;

        $students = Student::with(['Invoice', 'Attendance', 'Course', 'Fleet'])
            ->whereHas('expenses', function ($query) use ($expenseId) {
                $query->where('expense_id', $expenseId);
            })
            ->with(['expenses' => function ($query) use ($expenseId) {
                $query->where('expense_id', $expenseId);
            }])
            ->get();

        // Extract all unique `payment_entered_by` IDs from the pivots
        $enteredByIds = $students
            ->flatMap(function ($student) {
                return $student->expenses->pluck('pivot.payment_entered_by');
            })
            ->filter()
            ->unique();

        // Load the User + Administrator data for those IDs
        $enteredByAdmins = \App\Models\User::with('administrator')
            ->whereIn('id', $enteredByIds)
            ->get()
            ->keyBy('id');

        //Return response
        return response()->json([
            'students' => $students,
            'enteredByAdmins' => $enteredByAdmins
        ], 200);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function edit(expense $expense)
    {
        $expense = Expense::with('Students')->find($expense->id);
        return view('expenses.editExpense', compact('expense'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateexpenseRequest  $request
     * @param  \App\Models\expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateexpenseRequest $request, Expense $expense)
    {
        $messages = [
            'expenseGroupName.required' => 'Expense Group Name is required',
            'expenseAmount.required'   => 'Expense amount is required',
            'students.required' => 'Please select at least one student',
            'students.array' => 'Invalid student data format',
            'students.min' => 'Please select at least one student',
        ];

        try {
            $this->validate($request, [
                'expenseGroupName'  => 'required',
                'students'          => 'required|array|min:1'
            ], $messages);

            DB::beginTransaction();

            $post = $request->all();
            $students = $post['students'];

            $expense = Expense::find($post['expenseId']);

            // Check if expense exists
            if (!$expense) {
                throw new ModelNotFoundException('Expense not found');
            }

            // Update expense
            $expense->group = $post['expenseGroupName'];
            $expense->group_type = $post['expenseGroupType'] ?? null;
            $expense->description = $post['expenseDescription'] ?? null;
            $expense->amount = 0;
            $expense->edited_by = Auth::user()->administrator_id;
            $expense->save();

            // Clear previous student associations
            $expense->students()->detach();

            // Reattach students with expense_type
            foreach ($students as $data) {
                $fullName = trim($data['fname'] . ' ' . $data['mname'] . ' ' . $data['sname']);
                $student = havenUtils::student($fullName);

                $student->expenses()->attach($expense->id, [
                    'expense_type' => $data['expenses'][0]['pivot']['expense_type'],
                    'repeat'       => $data['expenses'][0]['pivot']['repeat'] ?? 0,
                    'amount'       => $data['expenses'][0]['pivot']['amount'] ?? 0,
                    'amount'       => $data['expenses'][0]['pivot']['amount'] ?? 0,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Expense updated successfully'], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Expense update failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'An error occurred while updating the expense. Please try again later.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function destroy(expense $expense)
    {
        $expense->delete();
        Alert::toast('Expense deleted', 'success');
        return redirect('/expenses');
    }

    public function removeStudent(StoreexpenseRequest $request)
    {
        $post = $request->all();

        DB::table('expense_student')->where('student_id', $request['student'])->where('expense_id', $request['expenseId'])->delete();

        return response()->json([$post], 200);
    }

    public function checkStudent(StoreexpenseRequest $request)
    {
        $request->validate([
            'student' => 'required',
            'expenseTypesOption' => 'required',
        ]);

        $student = Student::find($request->student);

        if (!$student?->invoice) {
            return response()->json([
                'feedback' => 'error',
                'message' => 'Student not found or not enrolled yet.'
            ], 200);
        }

        $option = ExpenseTypeOption::find($request->expenseTypesOption);

        if (!$option) {
            return response()->json([
                'feedback' => 'error',
                'message' => 'Invalid expense type option selected.'
            ], 200);
        }

        $optionName = $option->name;
        $fullName = trim($student->fname . ' ' . ($student->mname ?? '') . ' ' . $student->sname);

        //Check if student already has that option
        $existingExpenses = DB::table('expense_student')
            ->where('student_id', $student->id)
            ->where('expense_type', $option->id)
            ->get();

        if ($existingExpenses->count() > 0) {
            $expenseIds = $existingExpenses->pluck('expense_id')->toArray();
            $expenses = Expense::whereIn('id', $expenseIds)->get();
            $groupDates = $expenses->pluck('group')->filter()->unique()->toArray();
            $groupDateString = !empty($groupDates) ? implode(', ', $groupDates) : 'Unknown date';

            return response()->json([
                'feedback' => 'alreadyExists',
                'message' => "{$fullName} was already selected for {$optionName} expenses dated {$groupDateString}. Do you want to continue adding to another list?"
            ], 200);
        }

        // Calculate student's paid percentage once
        if ($student->invoice && $student->invoice->invoice_total > 0) {
            $paidPercent = ($student->invoice->invoice_amount_paid / $student->invoice->invoice_total) * 100;
        } else {
            $paidPercent = 0;
        }

        // Use the option's threshold
        if ($option->fees_percent_threshhold !== null && $paidPercent < $option->fees_percent_threshhold) {
            return response()->json([
                'feedback' => 'error',
                'message' => "{$fullName} cannot be selected for {$optionName}. There is K{$student->invoice->invoice_balance} balance that must be paid."
            ], 200);
        }

        // Use the option's period threshold
        if ($option->period_threshold !== null && $option->period_threshold !== 0) {
            $dateDifferenceDays = Carbon::parse($student->invoice->created_at)->diffInDays(Carbon::now());

            if ($dateDifferenceDays <= $option->period_threshold) {

                $daysDifference = $option->period_threshold - $dateDifferenceDays;

                return response()->json([
                    'feedback' => 'error',
                    'message' => "{$fullName} cannot be selected for {$optionName}. {$daysDifference} day(s) remaining before they can be selected."
                ], 200);
            }
        }

        return response()->json([
            'feedback' => 'success',
            'message' => 'Student added to list. Remember to click submit after selecting all students.'
        ], 200);
    }

    public function approveList(StoreexpenseRequest $request)
    {
        $request->validate([
            'expenseId' => 'required|exists:expenses,id',
            'approvedAmount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $expense = Expense::find($request->expenseId);

        $expense->approved_by = $user->administrator_id;
        $expense->approved_amount = $request->approvedAmount;
        $expense->approved = !$expense->approved;
        $expense->date_approved = Carbon::now();
        $expense->save();

        $admin = Administrator::with('user')->find($expense->added_by);

        try {
            if ($admin && $admin->user) {
                $admin->user->notify(new ExpenseApproved($expense, $user->administrator->fname));
            }
        } catch (\Exception $e) {
            // Optionally log the error or handle it gracefully
            Log::error('Failed to send expense approval notification: ' . $e->getMessage());
        }

        return response()->json($expense, 200);
    }

    public function download(expense $expense){

        $setting = $this->setting;
        $date = date('j F, Y');
        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca-9143-8ytf6/'.$expense->id);

        $template = 'pdf_templates.theoryExpense';

        $expense = Expense::with(['students' => function ($query) {
            $query->orderBy('fname', 'asc');
        }])->findOrFail($expense->id);

        if($expense->group_type == '39d41003-4f04-11f0-aa86-52540066f921'){
            $template = 'pdf_templates.roadTestExpense';
        }

        $pdf = PDF::loadView($template, compact('expense', 'qrCode','setting', 'date'));
        return $pdf->download('Daron Driving School-'.$expense->group.'-'.$expense->group_type.' Expense.pdf');
    }

    public function expensePaymentReport(expense $expense){

        if (!Auth::user()->hasRole(['superAdmin', 'financeAdmin', 'admin'])) {
            return response()->json([
                'message' => 'You do not have permission to make a payment.'
            ], 403);
        }

        $setting = $this->setting;
        $date = date('j F, Y');
        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/expense-payment-report/'.$expense->id);

        $template = 'pdf_templates.expensePaymentReport';

        $expense = Expense::with(['Students' => function ($query) {
            $query->orderBy('fname', 'asc');
        }])->findOrFail($expense->id);

        // Get unique payment_entered_by values
        $enteredByIds = $expense->students->pluck('pivot.payment_entered_by')->filter()->unique();

        $expenseTypeNames = ExpenseTypeOption::pluck('name', 'id')->toArray();
        $expensegroupTypeNames = ExpenseType::pluck('name', 'id')->toArray();

        // Get User -> Administrator once
        $enteredByAdmins = \App\Models\User::with('administrator')
            ->whereIn('id', $enteredByIds)
            ->get()
            ->keyBy('id');

            $pdf = PDF::loadView($template, compact(
                'expense',
                'qrCode',
                'setting',
                'date',
                'enteredByAdmins',
                'expenseTypeNames'
            ));

            $typeName = $expensegroupTypeNames[$expense->group_type] ?? 'Daron';

            return $pdf->download(
                $expense->group . ' - ' . $typeName . ' Expense Payment Report - Daron Driving School.pdf'
            );
    }

    public function autocompletestudentSearch(Request $request)
    {
        $search = $request->get('student');

        $students = \DB::table('students')
            ->whereNotNull('course_id')
            ->where('course_id', '!=', '')
            ->where(function ($query) use ($search) {
                $query->where('fname', 'LIKE', "%{$search}%")
                    ->orWhere('mname', 'LIKE', "%{$search}%")
                    ->orWhere('sname', 'LIKE', "%{$search}%");
            })
            ->select('id', 'fname', 'mname', 'sname')
            ->get();

        if ($students->isEmpty()) {
            $dataModified = [];
        }

        // Format response
        $dataModified = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => trim("{$student->fname} {$student->mname} {$student->sname}"),
            ];
        });

        return response()->json($dataModified);
    }


    public function makePayment(Request $request, $studentId, $expenseId)
    {
        if (!Auth::user()->hasRole(['superAdmin', 'financeAdmin'])) {
            return response()->json([
                'message' => 'You do not have permission to make a payment.'
            ], 403);
        }

        // Validate input first
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'in:Cash,Bank Transfer,Mobile Money'],
        ]);

        // Make sure student exists
        $student = Student::findOrFail($studentId);

        DB::transaction(function () use ($request, $student, $expenseId) {

            // Lock expense row to prevent double payment
            $expense = $student->expenses()
                ->where('expenses.id', $expenseId)
                ->lockForUpdate()
                ->firstOrFail(); // simpler than manual null check

            // Check if expense is approved
            if (!$expense->approved) {
                abort(403, 'Expense is not approved yet.');
            }

            // Validate pivot status
            if ($expense->pivot->status == 1) {
                abort(422, 'Student has already paid for this expense.');
            }

            if ($expense->pivot->repeat == 1) {
                abort(422, 'Student is repeating and cannot pay.');
            }

            if ($request->amount != $expense->pivot->amount) {
                abort(422, 'Payment amount must exactly match the expense amount.');
            }

            // Update the pivot table safely
            $student->expenses()->updateExistingPivot($expenseId, [
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'status' => 1,
                'payment_entered_by' => auth()->id(),
                'paid_at' => now(),
            ]);

            $updatedExpense = $student->expenses()->where('expenses.id', $expenseId)->first();

            // Notify student (if they have a user)
            if ($student->user) {
                $student->user->notify(new ExpensePaymentMade($student, $updatedExpense));
            }
        });

        return response()->json([
            'message' => 'Payment recorded successfully.'
        ]);
    }


    /**
     * Display a list of expenses for a specific student.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function studentExpenses(Request $request, $token)
    {
        if (!Auth::user()->hasRole(['superAdmin', 'financeAdmin'])) {
            Alert::toast('You do not have permission to make a payment', 'error');
            return redirect()->back();
        }

        $student = Student::with('expenses')->find($token);
        $expenses = $student->expenses()->orWherePivot('repeat', null)->orWherePivot('repeat', 0)->get();

        // Check if the student exists
        if (!$student) {
            Alert::error('Student Not Found', 'The student record could not be found, scan another document or contact the Admin.');
            return redirect()->to('/scan-to-pay');
        }

        if ($expenses->isEmpty()) {
            Alert::warning('No Expenses Found', 'No expenses list is available for this student.');
            return redirect()->to('/scan-to-pay');
        }

        return view('expenses.studentExpenseList', [
            'student' => $student,
        ]);
    }

    public function expensePaymentsList(Request $request)
    {
        // Capture the search keyword from the request if provided
        $search = $request->input('search.value');

        $expensePayments = ExpensePayment::with(['paymentUser.administrator', 'student', 'expense'])
        ->where('status', 1);

        $expenseTypeOptions = ExpenseTypeOption::with('expenseType')->get();

        $optionToTypeName = $expenseTypeOptions->mapWithKeys(fn ($option) => [
            $option->id => $option?->name ?? '-',
        ]);

        if ($search) {
            $expensePayments->where(function($q) use ($search) {
                $q->whereHas('expense', function ($q2) use ($search) {
                    $q2->where('group', 'like', "%$search%")
                       ->orWhere('type', 'like', "%$search%");
                })
                ->orWhereHas('student', function ($q3) use ($search) {
                    $q3->where('fname', 'like', "%$search%")
                       ->orWhere('sname', 'like', "%$search%");
                });
            });
        }

        return DataTables::eloquent($expensePayments)
        ->addColumn('student', fn ($payment) =>
            $payment->student
                ? trim($payment->student->fname . ' ' . $payment->student->mname . ' ' . $payment->student->sname)
                : '-'
        )
        ->addColumn('group', fn ($payment) => $payment->expense ? $payment->expense->group : '-')
        ->addColumn('expense_type', fn ($payment) => $optionToTypeName[$payment->expense_type] ?? '-')
        ->addColumn('amount', fn ($payment) => '<div class="text-end"><strong>K' . number_format($payment->amount, 2) . '</strong></div>')
        ->addColumn('payment_method', fn ($payment) => $payment->payment_method ?? '-')
        ->addColumn('date_paid', fn ($payment) => $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('j F, Y') : '-')
        ->addColumn('paid_by', function ($payment) {
            return optional($payment->paymentUser->administrator)
                ? $payment->paymentUser->administrator->fname . ' ' . $payment->paymentUser->administrator->sname
                : '-';
        })
        ->addColumn('actions', function ($payment) {
            $receipt = '';
            $reverse = '';
            $delete = '';

            // Roles allowed
            if (auth()->user()->hasAnyRole(['superAdmin', 'financeAdmin'])) {

                $receipt = '<form method="GET" action="' . url('expense-payment-receipt', $payment->id) . '">
                    <button class="dropdown-item nav-main-link btn download-confirm" type="submit">
                        <i class="fa fa-download me-3"></i> Receipt
                    </button>
                </form>';

                // Reverse allowed for superAdmin
                if (auth()->user()->hasRole('superAdmin')) {
                    $reverse = '<a href="javascript:void(0);" class="dropdown-item nav-main-link btn" onclick="reversePayment(' . $payment->id . ')">
                                    <i class="fa fa-undo me-3"></i> Reverse
                                </a>';
                }

                // Delete allowed for superAdmin if not approved
                if (auth()->user()->hasRole('superAdmin') && $payment->approved == false) {
                    $delete = '<form method="POST" action="' . url('expense-payments', $payment->id) . '" style="display:inline;">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="btn dropdown-item nav-main-link delete-confirm">
                                        <i class="fa fa-trash me-3"></i> Delete
                                    </button>
                               </form>';
                }
            }

            return '
                <div class="dropdown d-inline-block">
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown">Actions</button>
                    <div class="dropdown-menu dropdown-menu-end">
                        ' . $receipt . $reverse . $delete . '
                    </div>
                </div>
            ';
        })
        ->rawColumns(['amount', 'actions', 'action'])
        ->make(true);

    }


    public function downloadExpensePaymentReceipt($id)
    {
        // Find the payment
        $payment = ExpensePayment::with(['student', 'expense', 'paymentUser.administrator'])
                        ->findOrFail($id);

        // Optional: check permission
        if (!auth()->user()->hasAnyRole(['superAdmin', 'financeAdmin'])) {
            abort(403, 'Unauthorized.');
        }

        // Prepare PDF with thermal printer paper size
        $pdf = Pdf::loadView('pdf_templates.paymentReceipt', [
            'payment' => $payment
        ]);

        // Example for 58mm thermal roll: width ~ 164 points, height auto (use long height)
        $customPaper = [0, 0, 164, 500]; // width 58mm (164pt), height ~7 inch (500pt) or adjust

        $pdf->setPaper($customPaper);

        // Suggest filename
        $fileName = 'ExpensePaymentReceipt_' . $payment->id . '.pdf';

        return $pdf->download($fileName);
    }

}
