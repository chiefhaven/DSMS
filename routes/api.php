<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HomeController;


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

Route::get('invoicesHome', 'App\Http\Controllers\Api\HomeController@index')->middleware('auth');

Route::get('invoices', 'App\Http\Controllers\Api\InvoiceController@index')->middleware('auth');

Route::get('invoice-view/{id}', 'App\Http\Controllers\Api\InvoiceController@show')->middleware('auth');
