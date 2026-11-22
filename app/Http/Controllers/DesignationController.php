<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Models\HrDepartment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DesignationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view designations')->only(['index', 'fetch']);
        $this->middleware('permission:manage designations')->only(['store', 'update', 'destroy']);
    }

    public function index()
    {
        $departments = HrDepartment::orderBy('name')->get();

        return view('hrm.designations', compact('departments'));
    }

    public function fetch(): JsonResponse
    {
        return response()->json(Designation::with('hrDepartment')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('designations', 'name')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
            'hr_department_id' => ['required', Rule::exists('hr_departments', 'id')->whereNull('deleted_at')],
        ]);

        $designation = Designation::create($validated);

        return response()->json([
            'message' => 'Designation created successfully.',
            'designation' => $designation->load('hrDepartment'),
        ]);
    }

    public function update(Request $request, Designation $designation): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('designations', 'name')->ignore($designation->id)->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
            'hr_department_id' => ['required', Rule::exists('hr_departments', 'id')->whereNull('deleted_at')],
        ]);

        $designation->update($validated);

        return response()->json([
            'message' => 'Designation updated successfully.',
            'designation' => $designation->fresh()->load('hrDepartment'),
        ]);
    }

    public function destroy(Designation $designation): JsonResponse
    {
        $designation->delete();

        return response()->json(['message' => 'Designation deleted successfully.']);
    }
}
