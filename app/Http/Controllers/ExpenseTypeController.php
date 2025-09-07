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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class ExpenseTypeController extends Controller
{

    public function __construct()
    {
        $this->middleware(['role:superAdmin']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function index(Request $request): JsonResponse
    {

        $search = $request->input('search.value');

        $expenseTypes = ExpenseType::with(['expenseTypeOptions', 'licenceClasses'])
            ->orderBy('name', 'DESC');

        // 🔎 Search logic
        if ($search) {
            $expenseTypes->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('licenceClasses', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        return DataTables::of($expenseTypes)
            ->addColumn('type', function ($expenseType) {
                return '<strong>' . e($expenseType->name) . '</strong>';
            })

            ->addColumn('licence_classes', function ($expenseType) {
                return e($expenseType->licenceClasses->pluck('class')->implode(', '));
            })

            ->addColumn('description', function ($expenseType) {
                return e($expenseType->description ?: '-');
            })

            ->addColumn('options', function ($expenseType) {
                if ($expenseType->expenseTypeOptions->count()) {
                    $options = '<ol class="mb-0 ps-3">';
                    foreach ($expenseType->expenseTypeOptions as $option) {
                        $options .= '<li>'
                            . e($option->name)
                            . ' - <b>K' . number_format($option->amount_per_student ?? 0, 2) . '</b>'
                            . '<br><small class="text-muted">'
                            . e($option->fees_percent_threshhold ?? 0) . '% fees threshold, '
                            . e($option->period_threshold ?? 0) . ' days for selection'
                            . '</small></li>';
                    }
                    $options .= '</ol>';
                    return $options;
                }

                return e($expenseType->description ?: '-');
            })

            ->addColumn('status', function ($expenseType) {
                return $expenseType->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">Inactive</span>';
            })

            ->addColumn('actions', function ($expenseType) {
                $delete = $view = $edit = '';

                if (auth()->user()->hasAnyRole(['superAdmin', 'admin', 'financeAdmin'])) {
                    $view = '<a class="dropdown-item nav-main-link btn" href="' . url('/view-expense', $expenseType->id) . '">
                                <i class="fa fa-eye me-3"></i> View
                            </a>';

                    if (auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                        $edit = '<a class="dropdown-item nav-main-link btn"
                                    data-expense-type=\'' . htmlspecialchars(json_encode($expenseType), ENT_QUOTES, 'UTF-8') . '\'
                                    onclick="openEditExpenseType(this)">
                                    <i class="fa fa-pencil me-3"></i> Edit
                                </a>';

                        $delete = '<a href="#" class="dropdown-item nav-main-link btn"
                                    onclick="openDeleteExpenseType(' . htmlspecialchars(json_encode(['id' => $expenseType->id]), ENT_QUOTES, 'UTF-8') . ')">
                                    <i class="fa fa-trash me-3"></i> Delete
                                </a>';
                    }
                }

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $view . $edit . $delete . '
                        </div>
                    </div>
                ';
            })

            // ✅ Allow HTML in these columns
            ->rawColumns(['type', 'options', 'status', 'actions'])
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

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:expense_types,name',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'options' => 'nullable|array',
            'options.*.name' => 'required|string',
            'options.*.amount_per_student' => 'nullable|numeric|min:0',
            'options.*.fees_percent_threshhold' => 'nullable|numeric|min:0|max:100',
            'options.*.period_threshold' => 'nullable|numeric|min:0|max:100',
            'licence_classes' => 'nullable|array',
            'licence_classes.*' => 'uuid|exists:licence_classes,id',
        ]);

        $expenseType = ExpenseType::create([
            'id' => Str::uuid(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        // Attach selected licence classes
        if (!empty($validated['licence_classes'])) {
            $expenseType->licenceClasses()->sync($validated['licence_classes']);
        }

        // Create options
        if (!empty($validated['options'])) {
            foreach ($validated['options'] as $option) {
                $expenseType->expenseTypeOptions()->create([
                    'name' => $option['name'],
                    'amount_per_student' => $option['amount_per_student'] ?? 0,
                    'fees_percent_threshhold' => $option['fees_percent_threshhold'] ?? 0,
                    'period_threshold' => $option['period_threshold'] ?? 0,
                ]);
            }
        }

        return response()->json([
            'message' => 'Expense type created successfully.',
            'expense_type' => $expenseType->load('licenceClasses', 'expenseTypeOptions')
        ]);
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

    public function update(UpdateExpenseTypeRequest $request, $expenseTypeId)
    {
        $expenseType = ExpenseType::findOrFail($expenseTypeId);

        // Permission check
        if (!auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate input
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('expense_types', 'name')->ignore($expenseType->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'options' => 'nullable|array',
            'options.*.name' => 'required|string',
            'options.*.amount_per_student' => 'nullable|numeric|min:0',
            'options.*.fees_percent_threshhold' => 'nullable|numeric|min:0|max:100',
            'options.*.period_threshold' => 'nullable|numeric|min:0|max:100',
            'licence_classes' => 'nullable|array',
            'licence_classes.*' => 'uuid|exists:licence_classes,id',
        ]);

        // Update main expense type
        $expenseType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        // Sync licence classes
        if (isset($validated['licence_classes'])) {
            $expenseType->licenceClasses()->sync($validated['licence_classes']);
        }

        // Replace options (delete old and create new)
        $expenseType->expenseTypeOptions()->delete();

        if (!empty($validated['options'])) {
            foreach ($validated['options'] as $option) {
                $expenseType->expenseTypeOptions()->create([
                    'name' => $option['name'],
                    'amount_per_student' => $option['amount_per_student'] ?? 0,
                    'fees_percent_threshhold' => $option['fees_percent_threshhold'] ?? 0,
                    'period_threshold' => $option['period_threshold'] ?? 0,
                ]);
            }
        }

        return response()->json([
            'message' => 'Expense Type updated successfully.',
            'expense_type' => $expenseType->load('licenceClasses', 'expenseTypeOptions')
        ]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ExpenseType  $expenseType
     * @return \Illuminate\Http\Response
     */
    public function destroy($expenseType)
    {
        if (in_array($expenseType, [
            '39d3f058-4f04-11f0-aa86-52540066f921',
            '39d40542-4f04-11f0-aa86-52540066f921',
            '39d41003-4f04-11f0-aa86-52540066f921',
        ])) {
            return response()->json(['message' => 'Cannot delete these expense types'], 400);
        }

        // Find the expense type by ID
        $expenseType = ExpenseType::with('expenseTypeOptions')->findOrFail($expenseType);

        if (!$expenseType) {
            return response()->json(['message' => 'Expense Type not found.'], 404);
        }

        // Check if the user has permission to delete the expense type
        if (!auth()->user()->hasAnyRole(['superAdmin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $expenseType->expenseTypeOptions()->delete();
        // Delete the expense type
        $expenseType->delete();

        return response()->json(['message' => 'Deleted successfully.']);
    }

    public function fetch(Request $request): JsonResponse
    {
        // Validate the request
        $expenseTypes = ExpenseType::with('expenseTypeOptions')
            ->orderBy('name', 'DESC')->get();

        return response()->json($expenseTypes);
    }
}
