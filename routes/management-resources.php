<?php

use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseResourceController;
use App\Http\Controllers\InstructorController;
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
    ->middleware(['auth', 'permission:manage administration'])
    ->name('instructors.account.update');
Route::put('administrators/{administrator}/account', [StaffAccountController::class, 'updateAdministrator'])
    ->middleware(['auth', 'permission:manage administration'])
    ->name('administrators.account.update');

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
