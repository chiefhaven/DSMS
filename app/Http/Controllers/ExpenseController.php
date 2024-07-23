<?php

namespace App\Http\Controllers;

use App\Models\expense;
use App\Http\Requests\StoreexpenseRequest;
use App\Http\Requests\UpdateexpenseRequest;
use App\Models\Setting;
use Auth;
use PDF;
use RealRashid\SweetAlert\Facades\Alert;

class ExpenseController extends Controller
{
    protected $setting;

    public function __construct()
    {
        $this->middleware(['role:superAdmin'], ['role:admin']);
        $this->setting = Setting::find(1);
    }/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $expenses = Expense::with('Student')->orderBy('created_at', 'DESC')->paginate(10);

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

        $studentsCount = count($students);

        $expense = new expense();
        $expense->group = $post['expenseGroupName'];
        $expense->group_type = $post['expenseGroupType'];
        $expense->description = $post['expenseDescription'];
        $expense->amount = $studentsCount * $post['expenseAmount'];
        $expense->added_by = Auth::user()->administrator_id;

        $expense->save();
        //Get student id
        foreach ($students as $data) {
            $expenseId = Expense::orderBy('created_at', 'desc')->first()->id;
            $student = havenUtils::student($data['studentName']);
            $student->expense()->attach($expenseId, ['expense_type' => $data['expenseType']]);
        }

        if(!$expense->save()){
            return false;
        }

        $data = ['message' => 'Expense added successifuly'];
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
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function edit(expense $expense)
    {
        //
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
        //
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

    public function download(expense $expense){

        $setting = $this->setting;
        $date = date('j F, Y');
        $qrCode = havenUtils::qrCode('https://www.dsms.darondrivingschool.com/e8704ed2-d90e-41ca-9143-8ytf6/'.$expense->id);

        $template = 'pdf_templates.theoryExpense';

        if($expense->group_type == 'Road Test'){
            $template = 'pdf_templates.roadTestExpense';
        }

        $pdf = PDF::loadView($template, compact('expense', 'qrCode','setting', 'date'));
        return $pdf->download('Daron Driving School-'.$expense->group.'-'.$expense->group_type.' Expense.pdf');
    }
}
