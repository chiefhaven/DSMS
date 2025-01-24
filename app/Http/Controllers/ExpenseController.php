<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Http\Requests\StoreexpenseRequest;
use App\Http\Requests\UpdateexpenseRequest;
use App\Models\Administrator;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ExpenseCreated;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;
use DB;
use RealRashid\SweetAlert\Facades\Alert;

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
        if(Auth::user()->hasRole('admin')){
            $expenses = Expense::where('added_by', Auth::user()->administrator_id)->with('Students')->orderBy('created_at', 'DESC')->paginate(10);
        }
        else{
            $expenses = Expense::with('Students')->orderBy('created_at', 'DESC')->get();
        }
        return view('expenses.expenses', compact('expenses'));
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
        ];

        // Validate the request
        $this->validate($request, [
            'expenseGroupName'  =>'required',
            'expenseAmount'   =>'required',

        ], $messages);

        $post = $request->all();

        $students = $post['students'];

        $user = Auth::user();

        $admin = Administrator::where('id', $user->administrator_id)->firstOrFail();

        $expense = new expense();
        $expense->group = $post['expenseGroupName'];
        $expense->group_type = $post['expenseGroupType'];
        $expense->description = $post['expenseDescription'];
        $expense->amount = $post['expenseAmount'];
        $expense->added_by = $user->administrator_id;

        $expense->save();
        //Get student id
        foreach ($students as $data) {
            $expenseId = Expense::orderBy('created_at', 'desc')->first()->id;
            $student = havenUtils::student($data['studentName']);
            $student->expenses()->attach($expenseId, ['expense_type' => $data['expenseType']]);
        }

        if(!$expense->save()){
            return false;
        }

        $superAdmins = User::role('superAdmin')->get();

        foreach ($superAdmins as $superAdmin) {
            $superAdmin->notify(new ExpenseCreated($expense, $admin->fname));
        }

        $data = ['message' => 'Expense added successfuly'];
        return response()->json([$data], 200);
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
    public function update(UpdateexpenseRequest $request, expense $expense)
    {
        $messages = [
            'expenseGroupName.required' => 'Expense Group Name is required',
            'expenseAmount.required'   => 'Expense amount is required',
        ];

        // Validate the request
        $this->validate($request, [
            'expenseGroupName'  =>'required',
            'expenseAmount'   =>'required',

        ], $messages);

        $post = $request->all();

        $students = $post['students'];

        $expenseUpdate = Expense::find($post['expenseId']);

        if (!$expenseUpdate) {
            return response()->json(['error' => 'Expense not found'], 404);
        }

        // Proceed with updating and saving
        $expenseUpdate->group = $post['expenseGroupName'];
        $expenseUpdate->group_type = $post['expenseGroupType'];
        $expenseUpdate->description = $post['expenseDescription'];
        $expenseUpdate->amount = $post['expenseAmount'];
        $expenseUpdate->edited_by = Auth::user()->administrator_id;

        $expenseUpdate->save();

        //Get student id
        foreach ($students as $data) {
            $student = havenUtils::student($data['fname'].' '.$data['mname'].' '.$data['sname']);
            $student->expenses()->sync([
                $expenseUpdate->id => ['expense_type' => $data['expenses'][0]['pivot']['expense_type']]
            ]);
        }

        if(!$expenseUpdate->save()){
            return false;
        }

        $data = ['message' => 'Expense added successfuly'];
        return response()->json([$data], 200);
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
        $post = $request->all();

        $student = havenUtils::student($post['student']);

        $expenseType = $post['expenseType'];

        $expenseTypeSet = DB::table('expense_student')->where('student_id', $student->id)->where('expense_type', $request['expenseType'])->get();
        $expenseTypeCount = $expenseTypeSet->count();

        if($expenseTypeCount > 0){
            $data = [
                'feedback'=>'error',
                'message' => $post['student'].' was already selected for '.$post['expenseType'].' expenses dated <strong>'.Expense::find($expenseTypeSet[0]->expense_id)->group .'</strong>'
            ];
            return response()->json($data, 200);
        }

        switch ($expenseType) {
            case "Road Test":
                if(($student->invoice->invoice_amount_paid / $student->invoice->invoice_total) * 100 < $this->setting->fees_road_threshold){
                    $data = [
                        'feedback'=>'error',
                        'message' => $post['student'].' can not be selected for road test, There are balances that must be paid'
                    ];
                    return response()->json($data, 200);
                }
                break;
            case "TRN":
                if(($student->invoice->invoice_amount_paid / $student->invoice->invoice_total) * 100 < $this->setting->fees_trn_threshold){
                    $data = [
                        'feedback'=>'error',
                        'message' => $post['student'].' can not be selected for TRN, There are balances that must be paid'
                    ];
                    return response()->json($data, 200);
                }
                break;
            case "Highway Code I":
                if(($student->Invoice->invoice_amount_paid / $student->Invoice->invoice_total) * 100 < $this->setting->fees_code_i_threshold){
                    $data = [
                        'feedback'=>'error',
                        'message' => $post['student'].' can not be selected for Highway code I, There are balances that must be paid'
                    ];
                    return response()->json($data, 200);
                }
                break;
            case "Highway Code II":
                if(($student->Invoice->invoice_amount_paid / $student->Invoice->invoice_total) * 100 < $this->setting->fees_code_ii_threshold){
                    $data = [
                        'feedback'=>'error',
                        'message' => $post['student'].' can not be selected for Highway code II, There are balances that must be paid'
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
        $post = $request->all();

        $expense = Expense::find($post['expenseId']);
        $expense->approved_by = Auth::user()->administrator_id;
        $expense->approved_amount = $post['approvedAmount'];
        $expense->approved = !$expense->approved;
        $expense->date_approved = Carbon::now();

        $expense->save();

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
        $datas = \DB::table('students')
            ->where('course_id', '!=', '')
            ->whereNotNull('course_id')
            ->where(function($query) use ($request) {
            $query->where('fname', 'LIKE', "%{$request->student}%")
                  ->orWhere('mname', 'LIKE', "%{$request->student}%")
                  ->orWhere('sname', 'LIKE', "%{$request->student}%");
        })
        ->get();

        $dataModified = array();

        foreach ($datas as $data){
           $dataModified[] = $data->fname.' '.$data->mname.' '.$data->sname;
         }

        return response()->json($dataModified);
    }
}
