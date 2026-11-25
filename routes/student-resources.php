<?php

use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentAccountController;
use Illuminate\Support\Facades\Route;

Route::post('students/{student}/account/password-reset-link', [StudentAccountController::class, 'sendPasswordResetLink'])
    ->middleware(['auth', 'permission:manage student accounts', 'throttle:5,1'])
    ->name('students.account.password-reset-link');

Route::post('students/{student}/account/temporary-password', [StudentAccountController::class, 'generateTemporaryPassword'])
    ->middleware(['auth', 'permission:manage student accounts', 'throttle:5,1'])
    ->name('students.account.temporary-password');

Route::post('students/{student}/trn', [StudentController::class, 'updateTrn'])
    ->middleware('auth')
    ->name('students.trn.update');

Route::get('students/{student}/trn-picture', [StudentController::class, 'trnPicture'])
    ->middleware('auth')
    ->name('students.trn-picture');

Route::get('students/trn-approvals', [StudentController::class, 'trnApprovalQueue'])
    ->middleware(['auth', 'permission:approve student TRN'])
    ->name('students.trn-approvals.index');

Route::get('students/trn-approvals/{approval}/picture', [StudentController::class, 'trnApprovalPicture'])
    ->middleware(['auth', 'permission:approve student TRN'])
    ->name('students.trn-approvals.picture');

Route::post('students/trn-approvals/{approval}/approve', [StudentController::class, 'approveTrnChange'])
    ->middleware(['auth', 'permission:approve student TRN'])
    ->name('students.trn-approvals.approve');

Route::post('students/trn-approvals/{approval}/reject', [StudentController::class, 'rejectTrnChange'])
    ->middleware(['auth', 'permission:approve student TRN'])
    ->name('students.trn-approvals.reject');

Route::resource('students', StudentController::class)
    ->parameters(['students' => 'student'])
    ->middleware('auth');
