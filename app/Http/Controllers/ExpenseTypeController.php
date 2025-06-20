<?php

namespace App\Http\Controllers;

use App\Models\ExpenseType;
use App\Http\Requests\StoreExpenseTypeRequest;
use App\Http\Requests\UpdateExpenseTypeRequest;

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

class ExpenseTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function index(Request $request): JsonResponse
     {
         $search = $request->input('search.value');

         $expenseTypes = ExpenseType::with('expenseTypeOptions')
             ->orderBy('name', 'DESC');

         if ($search) {
             $expenseTypes->where(function($query) use ($search) {
                 $query->where('name', 'like', "%$search%")
                       ->orWhere('description', 'like', "%$search%");
             });
         }

         return DataTables::of($expenseTypes)
             ->addColumn('type', function ($expenseType) {
                 return '<strong>' . e($expenseType->name) . '</strong>';
             })

             ->addColumn('options', function ($expenseType) {
                $options = $expenseType->expenseTypeOptions->pluck('name')->join(', ');

                 return e($options ?: $expenseType->description);
             })

             ->addColumn('status', function ($expenseType) {
                 if ($expenseType->is_active) {
                     return '<span class="badge bg-success">Active</span>';
                 } else {
                     return '<span class="badge bg-danger">Inactive</span>';
                 }
             })

             // ✅ 'actions' column
             ->addColumn('actions', function ($expenseType) {
                 $view = $edit = '';

                 if (auth()->user()->hasAnyRole(['superAdmin', 'admin', 'financeAdmin'])) {
                     $view = '<a class="dropdown-item nav-main-link btn" href="' . url('/view-expense', $expenseType->id) . '">
                                 <i class="fa fa-eye me-3"></i> View
                             </a>';

                     if (auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                         $edit = '<a class="dropdown-item nav-main-link btn" href="' . url('/editexpense', $expenseType->id) . '">
                                     <i class="fa fa-pencil me-3"></i> Edit
                                 </a>';
                     }
                 }

                 return '
                     <div class="dropdown d-inline-block">
                         <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown">Actions</button>
                         <div class="dropdown-menu dropdown-menu-end">
                             ' . $view . $edit . '
                         </div>
                     </div>
                 ';
             })

             // ✅ Allow HTML for these columns
             ->rawColumns(['actions', 'type', 'options', 'status'])
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
     * @param  \App\Http\Requests\StoreExpenseTypeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreExpenseTypeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ExpenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function show(ExpenseType $expenseType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ExpenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function edit(ExpenseType $expenseType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateExpenseTypeRequest  $request
     * @param  \App\Models\ExpenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateExpenseTypeRequest $request, ExpenseType $expenseType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ExpenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function destroy(ExpenseType $expenseType)
    {
        //
    }
}
