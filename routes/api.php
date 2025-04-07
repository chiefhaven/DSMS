<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\mbiraStudentVersion;
use App\Http\Controllers\Api\studentController as ApiStudentController;
use App\Http\Controllers\Api\studentProfileController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StudentController;
use App\Models\Classroom;

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
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('resetPassword.api'); // password reset
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('resetPassword.api'); // password reset
Route::post('/otp', [AuthController::class, 'otp']);
Route::post('login', [AuthController::class, 'login'])->name('login.api'); // Login

Route::get('invoicesHome', [HomeController::class],'index')->middleware('auth');
Route::get('/invoices', [InvoiceController::class, 'index']);
Route::get('invoice-view/{id}', [InvoiceController::class, 'show'])->middleware('auth');


Route::get('/studentProfile', [studentProfileController::class, 'show'])->middleware('auth:sanctum')->name('studentProfile');
Route::get('/classroomDetails/{id}', [ApiStudentController::class, 'showClassRoom'])->middleware('auth:sanctum')->name('classroomDetails');
Route::get('/fleetDetails/{id}', [ApiStudentController::class, 'showFleet'])->middleware('auth:sanctum')->name('fleetDetails');
Route::get('/attendance', [studentProfileController::class, 'showAttendance'])->middleware('auth:sanctum')->name('attendance');
Route::get('/students', [StudentController::class, 'fetchStudents'])->name('students');

Route::get('/courses', [studentProfileController::class, 'courses'])->middleware('auth:sanctum')->name('courses');

Route::get('/student-mbira-version', [mbiraStudentVersion::class, 'index'])->name('student-mbira-version');

Route::get('/notifications', [studentProfileController::class, 'notifications'])->middleware('auth:sanctum')->name('notifications');
Route::get('/attendances', [studentProfileController::class, 'attendances'])->middleware('auth:sanctum')->name('attendances');
Route::get('/courseDetails', [studentProfileController::class, 'courseDetails'])->middleware('auth:sanctum')->name('courseDetails');


