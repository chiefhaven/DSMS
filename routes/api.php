<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MbiraStudentVersion;
use App\Http\Controllers\Api\studentController as ApiStudentController;
use App\Http\Controllers\Api\StudentProfileController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\InstructorPaymentController;
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
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('resetPassword.api');
Route::post('/forgot-password', [AuthController::class, 'resetPassword'])->name('forgotPassword.api');
Route::post('/otp', [AuthController::class, 'otp']);
Route::post('login', [AuthController::class, 'login'])->name('login.api'); // Login

Route::get('invoicesHome', [HomeController::class],'index')->middleware('auth');
Route::get('/invoices', [InvoiceController::class, 'index']);
Route::get('invoice-view/{id}', [InvoiceController::class, 'show'])->middleware('auth');


Route::get('/studentProfile', [StudentProfileController::class, 'show'])->middleware('auth:sanctum')->name('studentProfile');
Route::get('/classroomDetails/{id}', [ApiStudentController::class, 'showClassRoom'])->middleware('auth:sanctum')->name('classroomDetails');
Route::get('/fleetDetails/{id}', [ApiStudentController::class, 'showFleet'])->middleware('auth:sanctum')->name('fleetDetails');
Route::get('/attendance', [StudentProfileController::class, 'showAttendance'])->middleware('auth:sanctum')->name('attendance');
Route::get('/students', [StudentController::class, 'fetchStudents'])->name('students')->middleware('auth:sanctum')->name('students');
Route::get('/instructor-students', [StudentController::class, 'fetchInstructorStudents'])->name('InstructorStudents')->middleware('auth:sanctum');

Route::get('/courses', [StudentProfileController::class, 'courses'])->middleware('auth:sanctum')->name('courses');

Route::get('/student-mbira-version', [MbiraStudentVersion::class, 'index'])->name('student-mbira-version');

Route::get('/notifications', [StudentProfileController::class, 'notifications'])->middleware('auth:sanctum')->name('notifications');
Route::get('/attendances', [StudentProfileController::class, 'attendances'])->middleware('auth:sanctum')->name('attendances');
Route::get('/courseDetails', [StudentProfileController::class, 'courseDetails'])->middleware('auth:sanctum')->name('courseDetails');

Route::post('/bonuses/pay-early', [InstructorPaymentController::class, 'store'])->middleware('auth:sanctum')->name('courseDetails');;


