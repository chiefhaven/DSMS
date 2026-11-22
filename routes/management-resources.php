<?php

use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseResourceController;
use App\Http\Controllers\HrDepartmentController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SalaryGradeController;
use App\Http\Controllers\StaffAccountController;
use App\Http\Controllers\InstructorPaymentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::resource('invoices', InvoiceController::class)
    ->middleware('auth');

Route::resource('payments', PaymentController::class)
    ->only(['index', 'store', 'destroy'])
    ->middleware('auth');

Route::resource('instructors', InstructorController::class)
    ->except('show')
    ->middleware('auth');
Route::resource('instructors', InstructorController::class)
    ->only('show');
Route::put('instructors/{instructor}/account', [StaffAccountController::class, 'updateInstructor'])
    ->middleware(['auth', 'permission:manage instructor accounts'])
    ->name('instructors.account.update');
Route::post('instructors/{instructor}/account/password-reset-link', [StaffAccountController::class, 'sendInstructorPasswordResetLink'])
    ->middleware(['auth', 'permission:manage instructor accounts', 'throttle:5,1'])
    ->name('instructors.account.password-reset-link');
Route::post('instructors/{instructor}/account/temporary-password', [StaffAccountController::class, 'generateInstructorTemporaryPassword'])
    ->middleware(['auth', 'permission:manage instructor accounts', 'throttle:5,1'])
    ->name('instructors.account.temporary-password');
Route::put('administrators/{administrator}/account', [StaffAccountController::class, 'updateAdministrator'])
    ->middleware(['auth', 'permission:manage administrator accounts'])
    ->name('administrators.account.update');
Route::post('administrators/{administrator}/account/password-reset-link', [StaffAccountController::class, 'sendAdministratorPasswordResetLink'])
    ->middleware(['auth', 'permission:manage administrator accounts', 'throttle:5,1'])
    ->name('administrators.account.password-reset-link');
Route::post('administrators/{administrator}/account/temporary-password', [StaffAccountController::class, 'generateAdministratorTemporaryPassword'])
    ->middleware(['auth', 'permission:manage administrator accounts', 'throttle:5,1'])
    ->name('administrators.account.temporary-password');

Route::resource('administrators', AdministratorController::class)
    ->middleware('auth');

Route::resource('expenses', ExpenseController::class)
    ->except('show')
    ->middleware('auth');
Route::resource('expenses', ExpenseResourceController::class)
    ->only('show')
    ->middleware('auth');

Route::resource('instructor-payments', InstructorPaymentController::class)
    ->only(['index', 'store'])
    ->middleware('auth');

// HRM module
Route::middleware('auth')->prefix('hrm')->group(function () {
    Route::view('leaves', 'hrm.leaves')->name('leaves.index');
    Route::get('settings', function () {
        return view('hrm.settings', [
            'payeConfig' => \App\Models\Setting::payeConfig(),
            'pensionConfig' => \App\Models\Setting::pensionConfig(),
        ]);
    })->name('hrm.settings');

    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/fetch', [PayrollController::class, 'fetch'])->name('payroll.fetch');
    Route::get('payroll/summary', [PayrollController::class, 'summary'])->name('payroll.summary');
    Route::get('payroll/employees', [PayrollController::class, 'employees'])->name('payroll.employees');
    Route::get('payroll/bonus-preview', [PayrollController::class, 'bonusPreview'])->name('payroll.bonus-preview');
    Route::get('payroll/bonus-previews', [PayrollController::class, 'bonusPreviews'])->name('payroll.bonus-previews');
    Route::get('payroll/paye-settings', [PayrollController::class, 'payeSettings'])->name('payroll.paye-settings');
    Route::put('payroll/paye-settings', [PayrollController::class, 'updatePayeSettings'])->name('payroll.paye-settings.update');
    Route::get('payroll/pension-settings', [PayrollController::class, 'pensionSettings'])->name('payroll.pension-settings');
    Route::put('payroll/pension-settings', [PayrollController::class, 'updatePensionSettings'])->name('payroll.pension-settings.update');
    Route::get('payroll/groups', [PayrollController::class, 'groups'])->name('payroll.groups');
    Route::get('payroll/groups/{payroll_group}', [PayrollController::class, 'showGroup'])->name('payroll.groups.show');
    Route::get('payroll/groups/{payroll_group}/export-standard-bank-csv', [PayrollController::class, 'exportStandardBankCsv'])->name('payroll.groups.export-standard-bank-csv');
    Route::get('payroll/groups/{payroll_group}/export-paye-csv', [PayrollController::class, 'exportPayeCsv'])->name('payroll.groups.export-paye-csv');
    Route::post('payroll/groups/{payroll_group}/pay', [PayrollController::class, 'payGroup'])->name('payroll.groups.pay');
    Route::put('payroll/groups/{payroll_group}', [PayrollController::class, 'updateGroup'])->name('payroll.groups.update');
    Route::delete('payroll/groups/{payroll_group}', [PayrollController::class, 'destroyGroup'])->name('payroll.groups.destroy');
    Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::post('payroll/{payroll}/pay', [PayrollController::class, 'pay'])->name('payroll.pay');
    Route::get('payroll/{payroll}/payslip', [PayrollController::class, 'payslip'])->name('payroll.payslip');
    Route::post('payroll/{payroll}/email-payslip', [PayrollController::class, 'emailPayslip'])
        ->middleware('throttle:10,1')
        ->name('payroll.email-payslip');
    Route::put('payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update');
    Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');

    Route::get('departments', [HrDepartmentController::class, 'index'])->name('hr-departments.index');
    Route::get('departments/fetch', [HrDepartmentController::class, 'fetch'])->name('hr-departments.fetch');
    Route::post('departments', [HrDepartmentController::class, 'store'])->name('hr-departments.store');
    Route::put('departments/{hr_department}', [HrDepartmentController::class, 'update'])->name('hr-departments.update');
    Route::delete('departments/{hr_department}', [HrDepartmentController::class, 'destroy'])->name('hr-departments.destroy');

    Route::get('designations', [DesignationController::class, 'index'])->name('designations.index');
    Route::get('designations/fetch', [DesignationController::class, 'fetch'])->name('designations.fetch');
    Route::post('designations', [DesignationController::class, 'store'])->name('designations.store');
    Route::put('designations/{designation}', [DesignationController::class, 'update'])->name('designations.update');
    Route::delete('designations/{designation}', [DesignationController::class, 'destroy'])->name('designations.destroy');

    Route::get('salary-grades', [SalaryGradeController::class, 'index'])->name('salary-grades.index');
    Route::get('salary-grades/fetch', [SalaryGradeController::class, 'fetch'])->name('salary-grades.fetch');
    Route::post('salary-grades', [SalaryGradeController::class, 'store'])->name('salary-grades.store');
    Route::put('salary-grades/{salary_grade}', [SalaryGradeController::class, 'update'])->name('salary-grades.update');
    Route::delete('salary-grades/{salary_grade}', [SalaryGradeController::class, 'destroy'])->name('salary-grades.destroy');
});
