<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Http\Requests\StoreexpenseRequest;
use App\Http\Requests\UpdateexpenseRequest;
use App\Models\Administrator;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ExpenseApproved;
use App\Notifications\ExpenseCreated;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;
use DB;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class ExpenseController extends Controller
{
    protected $setting;

    public function __construct()
    {
        $this->middleware(['role:superAdmin|admin']);
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
                    ->orWhere('type', 'like', "%$search%");
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
                return $expense->group_type;
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
                $edit = '';
                $delete = '';
                $review = '';

                // Check if user has either role
                if (auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {

                    // Edit only allowed if not approved
                    if ($expense->approved != true) {
                        $edit = '<a class="dropdown-item nav-main-link btn" href="' . url('/editexpense', $expense->id) . '">
                                    <i class="fa fa-pencil me-3"></i> Edit
                                </a>';
                    }

                    // Download logic
                    if ($expense->group_type !== 'TRN') {
                        if ($expense->approved == true) {
                            $download = '<form class="dropdown-item nav-main-link" method="GET" action="' . url('expensedownload', $expense->id) . '">
                                            ' . csrf_field() . '
                                            <button class="btn download-confirm" type="submit">
                                                <i class="fa fa-download me-3"></i> Download
                                            </button>
                                         </form>';
                        } else {
                            $download = '<p class="dropdown-item text-danger">Download not available</p>';
                        }
                    } else {
                        $download = '<p class="dropdown-item text-success">Go to student profile for TRN reference</p>';
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
                                        <button type="submit" class="btn dropdown-item nav-main-link delete-confirm text-danger">
                                            <i class="fa fa-trash me-3"></i> Delete
                                        </button>
                                   </form>';
                    }
                }

                // Combine and return the full dropdown
                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $download . $review . $edit . $delete . '
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
        $messages = [
            'expenseGroupName.required' => 'Expense Group Name is required',
            'expenseAmount.required'   => 'Expense amount is required',
            'students.required' => 'Please select at least one student',
            'students.array' => 'Invalid student data format',
            'students.min' => 'Please select at least one student',
        ];

        $this->validate($request, [
            'expenseGroupName'  => 'required',
            'expenseAmount'     => 'required',
            'students'          => 'required|array|min:1'
        ], $messages);

        $post = $request->all();
        $students = $post['students'];

        $user = Auth::user();
        $admin = Administrator::findOrFail($user->administrator_id);

        $expense = new Expense();
        $expense->group = $post['expenseGroupName'];
        $expense->group_type = $post['expenseGroupType'] ?? null;
        $expense->description = $post['expenseDescription'] ?? null;
        $expense->amount = $post['expenseAmount'];
        $expense->added_by = $user->administrator_id;
        $expense->save();

        foreach ($students as $data) {
            $student = havenUtils::student($data['studentName']);
            $student->expenses()->attach($expense->id, [
                'expense_type' => $data['expenseType']
            ]);
        }

        // Notify super admins
        $superAdmins = User::role('superAdmin')->get();
        foreach ($superAdmins as $superAdmin) {
            $superAdmin->notify(new ExpenseCreated($expense, $admin->fname));
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
        $expenseId = $expense->id;
        $expenseStudents = Student::with('Invoice', 'Attendance', 'Course', 'Fleet')->whereHas('expenses', function ($query) use ($expenseId) {
            $query->where('expense_id', $expenseId);
        })
        ->with(['expenses' => function ($query) use ($expenseId) {
            $query->where('expense_id', $expenseId);
        }])
        ->get();

        return response()->json($expenseStudents);
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
                'expenseAmount'     => 'required',
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
            $expense->amount = $post['expenseAmount'];
            $expense->edited_by = Auth::user()->administrator_id;
            $expense->save();

            // Clear previous student associations
            $expense->students()->detach();

            // Reattach students with expense_type
            foreach ($students as $data) {
                $fullName = trim($data['fname'] . ' ' . $data['mname'] . ' ' . $data['sname']);
                $student = havenUtils::student($fullName);

                $student->expenses()->attach($expense->id, [
                    'expense_type' => $data['expenses'][0]['pivot']['expense_type']
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
        ]);

        $post = $request->all();

        $student = Student::find($post['student']);

        // $student = havenUtils::student($post['student']);

        $expenseType = $post['expenseType'];

        $expenseTypeSet = DB::table('expense_student')->where('student_id', $student->id)->where('expense_type', $request['expenseType'])->get();
        $expenseTypeCount = $expenseTypeSet->count();

        $fullName = trim($student->fname . ' ' . ($student->mname ?? '') . ' ' . $student->sname);


        if ($expenseTypeCount > 0) {
            $expense = Expense::find($expenseTypeSet[0]->expense_id);
            $groupDate = $expense ? $expense->group : 'Unknown date';


            $data = [
                'feedback' => 'error',
                'message' => "{$fullName} was already selected for {$post['expenseType']} expenses dated <strong>{$groupDate}</strong>"
            ];

            return response()->json($data, 200); // or 200 if you prefer
        }

        switch ($expenseType) {
            case "Road Test":
                if(($student->invoice->invoice_amount_paid / $student->invoice->invoice_total) * 100 < $this->setting->fees_road_threshold){
                    $data = [
                        'feedback'=>'error',
                        'message' => "{$fullName} can not be selected for road test, There is K{$student->invoice->invoice_balance} balance that must be paid"
                    ];
                    return response()->json($data, 200);
                }
                break;
            case "TRN":
                if(($student->invoice->invoice_amount_paid / $student->invoice->invoice_total) * 100 < $this->setting->fees_trn_threshold){
                    $data = [
                        'feedback'=>'error',
                        'message' => "{$fullName} can not be selected for TRN, There are balances that must be paid"
                    ];
                    return response()->json($data, 200);
                }
                break;
            case "Highway Code I":
                if(($student->Invoice->invoice_amount_paid / $student->Invoice->invoice_total) * 100 < $this->setting->fees_code_i_threshold){
                    $data = [
                        'feedback'=>'error',
                        'message' => "{$fullName} can not be selected for Highway code I, There are balances that must be paid"
                    ];
                    return response()->json($data, 200);
                }
                break;
            case "Highway Code II":
                if(($student->Invoice->invoice_amount_paid / $student->Invoice->invoice_total) * 100 < $this->setting->fees_code_ii_threshold){
                    $data = [
                        'feedback'=>'error',
                        'message' => "{$fullName} can not be selected for Highway code II, There are balances that must be paid"
                    ];
                    return response()->json($data, 200);
                }
                break;
            default:
                $data = [
                    'feedback'=>'error',
                    'message' => 'Something wrong happened!'
                ];
                return response()->json($data, 200);
        }

        $data = [
            'feedback'=>'success',
            'message' => 'Student added to list remember to click submit after selecting all students'
        ];
        return response()->json($data, 200);

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

        if($expense->group_type == 'Road Test'){
            $template = 'pdf_templates.roadTestExpense';
        }

        $pdf = PDF::loadView($template, compact('expense', 'qrCode','setting', 'date'));
        return $pdf->download('Daron Driving School-'.$expense->group.'-'.$expense->group_type.' Expense.pdf');
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
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:255',
        ]);

        $student = Student::findOrFail($studentId);

        $expense = Expense::findOrFail($expenseId);

        // Check if the expense is approved
        if (!$expense->approved) {
            return response()->json(['message' => 'Expense is not approved yet.'], 403);
        }

        // Check if the student is associated with the expense
        if (!$student->expenses()->where('expenses.id', $expenseId)->exists()) {
            return response()->json(['message' => 'Expense not found for this student.'], 404);
        }

        // Update pivot table data
        $student->expenses()->updateExistingPivot($expenseId, [
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => 'paid',
            'payment_entered_by' => auth()->id(),
            'paid_at' => now(),
        ]);

        return response()->json(['message' => 'Payment recorded successfully.']);
    }


    public function studentExpenses(Request $request, $token)
    {
        $student = Student::with('expenses')->find($token);

        if (!$student) {
            Alert::error('Student Not Found', 'The student record could not be found, scan another document or contact the Admin.');
            return redirect()->to('/scan-to-pay');
        }

        if ($student->expenses->isEmpty()) {
            Alert::warning('No Expenses Found', 'No expenses list is available for this student.');
            return redirect()->to('/scan-to-pay');
        }

        return view('expenses.studentExpenseList', [
            'student' => $student,
        ]);
    }

}
