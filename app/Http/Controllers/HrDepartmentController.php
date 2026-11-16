<?php

namespace App\Http\Controllers;

use App\Models\HrDepartment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HrDepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view hr departments')->only(['index', 'fetch']);
        $this->middleware('permission:manage hr departments')->only(['store', 'update', 'destroy']);
    }

    public function index()
    {
        return view('hrm.departments');
    }

    public function fetch(): JsonResponse
    {
        return response()->json(HrDepartment::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hr_departments', 'name')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $department = HrDepartment::create($validated);

        return response()->json([
            'message' => 'Department created successfully.',
            'department' => $department,
        ]);
    }

    public function update(Request $request, HrDepartment $hr_department): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hr_departments', 'name')->ignore($hr_department->id)->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $hr_department->update($validated);

        return response()->json([
            'message' => 'Department updated successfully.',
            'department' => $hr_department->fresh(),
        ]);
    }

    public function destroy(HrDepartment $hr_department): JsonResponse
    {
        $hr_department->delete();

        return response()->json(['message' => 'Department deleted successfully.']);
    }
}
