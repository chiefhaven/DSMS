<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\HomeController as ApiHomeController;
use App\Http\Controllers\Api\InvoiceController as ApiInvoiceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MbiraStudentVersion;
use App\Http\Controllers\Api\studentController as ApiStudentController;
use App\Http\Controllers\Api\StudentProfileController;
use App\Http\Controllers\BulkAttendanceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpensePaymentController;
use App\Http\Controllers\ExpenseTypeController;
use App\Http\Controllers\havenUtils;
use App\Http\Controllers\InstructorPaymentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\VehicleTrackerController;

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

Route::post('/forgot-password', [AuthController::class, 'forgotPasswordReset'])->name('forgotPasswordReset.api'); //send password reset email

Route::post('/otp', [AuthController::class, 'otp']);
Route::post('login', [AuthController::class, 'login'])->name('login.api'); // Login

Route::get('invoicesHome', [HomeController::class],'index')->middleware('auth');
Route::get('/invoices', [ApiInvoiceController::class, 'index']);
Route::get('/fetchInvoices', [InvoiceController::class, 'fetchInvoices'])->middleware('auth');
Route::get('invoice-view/{id}', [ApiInvoiceController::class, 'show'])->middleware('auth');


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

Route::get('/student-lessons', [havenUtils::class, 'getStudentLessons'])
    ->middleware('auth:sanctum')
    ->name('api.student-lessons');

Route::get('/lessons', [havenUtils::class, 'getLessons'])
    ->middleware('auth:sanctum')
    ->name('api.lessons');

Route::get("/bulk-attendances", [BulkAttendanceController::class, 'index'])->middleware('auth')->name('api.bulk-attendances');
Route::post("/store-bulk-attendance", [BulkAttendanceController::class, 'store'])->middleware('auth')->name('api.store-bulk-attendance');
Route::post("/update-bulk-attendance", [BulkAttendanceController::class, 'update'])->middleware('auth')->name('api.update-bulk-attendance');
Route::delete('/bulk-attendance/{bulkAttendance}', [BulkAttendanceController::class, 'destroy'])
    ->middleware('auth')
    ->name('api.bulk-attendance.destroy');

Route::post('/bonuses/pay-early', [InstructorPaymentController::class, 'store'])->middleware('auth:sanctum')->name('pay-insturctor-bonuses');

Route::get('/dashboardSummary', [ApiHomeController::class, 'dashboardSummary'])->middleware('auth:sanctum')->name('dashboardSummary');

Route::get('/expenses', [ExpenseController::class, 'fetchExpenses'])->name('payments')->middleware('auth:sanctum');

Route::get('/expense-types', [ExpenseTypeController::class, 'index'])->name('api.expense-types')->middleware('auth:sanctum');

Route::get('/fetch-expense-types', [ExpenseTypeController::class, 'fetch'])->name('fetch-expense-types')->middleware('auth:sanctum');

Route::get('/viewExpenseData', [ExpenseController::class, 'show'])->middleware('auth')->name('viewExpenseData');

Route::get('/payments', [PaymentController::class, 'fetchPayments'])->name('payments')->middleware('auth:sanctum');

Route::get('/expense-payments', [ExpensePaymentController::class, 'index'])->name('expense.expensePayments')->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->post('/reverse-payment/{id}', [ExpensePaymentController::class, 'reverseExpensePayment'])->name('reverseExpensePayments');
Route::middleware('auth:sanctum')->post('/delete-payment/{id}', [ExpensePaymentController::class, 'destroy'])->name('deleteExpensePayments');

Route::prefix('expense-types')->group(function () {
    Route::post('/', [ExpenseTypeController::class, 'store']);
    Route::put('/{id}', [ExpenseTypeController::class, 'update']);
    Route::get('/', [ExpenseTypeController::class, 'index']);
    Route::delete('/{id}', [ExpenseTypeController::class, 'destroy']);
});

Route::post('/studentExpensePayment/{student}/{expense}', [ExpensePaymentController::class, 'store'])
    ->name('makeExpensePayment')
    ->middleware('auth:sanctum');

Route::get('/getPaymentMethods', [PaymentMethodController::class, 'fetchPaymentMethods'])->name('paymentMethods')->middleware('auth:sanctum');

Route::post('/save-vehicle-location', [VehicleTrackerController::class, 'store'])->name('save-vehicle-location')->middleware('auth:sanctum');

Route::get('/get-all-vehicle-locations', [VehicleTrackerController::class, 'show'])->name('vehicle-locations')->middleware('auth:sanctum');

Route::get('/show-vehicle-geo-data/{vehicleId}', [VehicleTrackerController::class, 'showVehicleGeoData'])->name('vehicle-location')->middleware('auth:sanctum');

Route::get('/getExpenceTypeOption/{id}', [havenUtils::class, 'getExpenceTypeOption'])->name('getExpenceTypeOption')->middleware('auth:sanctum');

Route::get('/student-lesson-attendances-count', [havenUtils::class, 'StudentLessonAttendancesCount'])->middleware('auth:sanctum')->name('StudentLessonAttendancesCount');

