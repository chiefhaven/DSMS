<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HomeController;
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

Route::get('invoicesHome', 'App\Http\Controllers\Api\HomeController@index')->middleware('auth');

Route::get('invoices', 'App\Http\Controllers\Api\InvoiceController@index')->middleware('auth')->name('invoices');

Route::get('invoice-view/{id}', 'App\Http\Controllers\Api\InvoiceController@show')->middleware('auth');


Route::get('/studentProfile', [studentProfileController::class, 'show'])->middleware('auth:sanctum')->name('studentProfile');
