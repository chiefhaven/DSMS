<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\studentProfileController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [AuthController::class, 'register'])->name('register.api'); // Signup
Route::post('login', [AuthController::class, 'login'])->name('login.api'); // Login

Route::apiResource('posts', PostController::class)->middleware('auth:sanctum');

Route::get('invoicesHome', [HomeController::class],'index')->middleware('auth');
Route::get('invoices', [InvoiceController::class, 'index'])->middleware('auth')->name('invoices');
Route::get('invoice-view/{id}', [InvoiceController::class, 'show'])->middleware('auth');


Route::get('/studentProfile', [studentProfileController::class, 'show'])->middleware('auth:sanctum')->name('studentProfile');
Route::get('/attendance', [studentProfileController::class, 'showAttendance'])->middleware('auth:sanctum')->name('attendance');
