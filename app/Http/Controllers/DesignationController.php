<?php

namespace App\Http\Controllers;

use App\Models\Designation;
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
        return view('hrm.designations');
    }

    public function fetch(): JsonResponse
    {
        return response()->json(Designation::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('designations', 'name')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $designation = Designation::create($validated);

        return response()->json([
            'message' => 'Designation created successfully.',
            'designation' => $designation,
        ]);
    }

    public function update(Request $request, Designation $designation): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('designations', 'name')->ignore($designation->id)->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $designation->update($validated);

        return response()->json([
            'message' => 'Designation updated successfully.',
            'designation' => $designation->fresh(),
        ]);
    }

    public function destroy(Designation $designation): JsonResponse
    {
        $designation->delete();

        return response()->json(['message' => 'Designation deleted successfully.']);
    }
}
