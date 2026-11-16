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

Route::resource('students', StudentController::class)
    ->parameters(['students' => 'student'])
    ->middleware('auth');
