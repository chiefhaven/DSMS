<?php

namespace App\Http\Controllers;

use App\Models\SalaryGrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryGradeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view salary grades')->only(['index', 'fetch']);
        $this->middleware('permission:manage salary grades')->only(['store', 'update', 'destroy']);
    }

    public function index()
    {
        return redirect()->to(route('payroll.index') . '#salary-grades-tab');
    }

    public function fetch(): JsonResponse
    {
        return response()->json(SalaryGrade::orderBy('code')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('salary_grades', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
        ]);

        $salaryGrade = SalaryGrade::create($validated);

        return response()->json([
            'message' => 'Salary grade created successfully.',
            'salary_grade' => $salaryGrade,
        ]);
    }

    public function update(Request $request, SalaryGrade $salary_grade): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('salary_grades', 'code')->ignore($salary_grade->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
        ]);

        $salary_grade->update($validated);

        return response()->json([
            'message' => 'Salary grade updated successfully.',
            'salary_grade' => $salary_grade->fresh(),
        ]);
    }

    public function destroy(SalaryGrade $salary_grade): JsonResponse
    {
        if ($salary_grade->administrators()->withTrashed()->exists()
            || $salary_grade->instructors()->withTrashed()->exists()) {
            return response()->json([
                'message' => 'Reassign this salary grade before deleting it.',
            ], 422);
        }

        $salary_grade->delete();

        return response()->json(['message' => 'Salary grade deleted successfully.']);
    }
}
