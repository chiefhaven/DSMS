<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\InvoiceSettingController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTemplateController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


include_once('install_r.php');

Route::post('/', [HomeController::class,'index'])->middleware(['auth'])->name('dashboard');
Route::get('/', [HomeController::class,'index'])->middleware(['auth'])->name('dashboard');

Auth::routes();

Route::post('/send-notification/{id}', [NotificationController::class, 'sendSMS'])->middleware('auth')->name('notification');
Route::get('/sms-templates', [NotificationTemplateController::class, 'create'])->middleware('auth')->name('sms_templates');
Route::post('/update-notification-templates', [NotificationTemplateController::class, 'update'])->middleware('auth')->name('update_notification_templates');


Route::get('/students', [StudentController::class, 'index'])->middleware('auth')->name('students');
Route::get('/viewstudent/{id}', [StudentController::class, 'show'])->middleware('auth')->name('viewStudent');
Route::get('/addstudent', [StudentController::class, 'create'])->middleware('auth')->name('addstudent');
Route::post('/storestudent', [StudentController::class, 'store'])->middleware('auth')->name('storestudents');
Route::post('/edit-student/{id}', [StudentController::class, 'edit'])->middleware('auth')->name('editstudent');
Route::delete('/student-delete/{id}', [StudentController::class, 'destroy'])->middleware('auth')->name('students_delete');
Route::post('/student-update', [StudentController::class, 'update'])->middleware('auth')->name('editstudent');
Route::post('/trafic-card-reference-letter/{id}', [StudentController::class, 'trafficCardReferenceLetter'])->middleware('auth')->name('student_traffic_card');
Route::post('/aptitude-test-reference-letter/{id}', [StudentController::class, 'aptitudeTestReferenceLetter'])->middleware('auth')->name('student_aptitude_test');
Route::post('/second-aptitude-test-reference-letter/{id}', [StudentController::class, 'secondAptitudeTestReferenceLetter'])->middleware('auth')->name('students_final_aptitude');
Route::post('/final-test-reference-letter/{id}', [StudentController::class, 'finalReferenceLetter'])->middleware('auth')->name('final-test-report');
Route::post('/lesson-report/{id}', [StudentController::class, 'lessonReport'])->middleware('auth')->name('lesson-report');
Route::get('/search-student', [StudentController::class, 'search'])->middleware('auth')->name('searchStudent');

Route::get('/attendances', [AttendanceController::class, 'index'])->middleware('auth')->name('attendances');
Route::get('/addattendance', [AttendanceController::class, 'create'])->middleware('auth')->name('addattendance');
Route::post('/storeattendance', [AttendanceController::class, 'store'])->middleware('auth')->name('storeattendance');
Route::post('/editattendance/{id}', [AttendanceController::class, 'edit'])->middleware('auth')->name('editattendance');
Route::post('/updateattendance', [AttendanceController::class, 'update'])->middleware('auth')->name('updateattendance');
Route::delete('/deleteattendance/{id}', [AttendanceController::class, 'destroy'])->middleware('auth')->name('deleteattendance');
Route::get('/attendance-student-search', [AttendanceController::class, 'autocompletestudentSearch'])->name('attendance-student-search');

Route::get('/courses', [CourseController::class, 'index'])->middleware('auth')->name('courses');
Route::get('/view-course/{id}', [CourseController::class, 'show'])->middleware('auth')->name('courses');
Route::get('/addcourse', [CourseController::class, 'create'])->middleware('auth')->name('addcourse');
Route::post('/storecourse', [CourseController::class, 'store'])->middleware('auth')->name('editcourse');
Route::post('/edit-course/{id}', [CourseController::class, 'edit'])->middleware('auth')->name('edit-course');
Route::delete('/delete-course/{id}', [CourseController::class, 'destroy'])->middleware('auth')->name('courses');
Route::post('/updatecourse', [CourseController::class, 'update'])->middleware('auth')->name('courses');

Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('auth')->name('invoices');
Route::get('/view-invoice/{id}', [InvoiceController::class, 'show'])->middleware('auth')->name('view-invoice');
Route::get('/invoice-pdf/{id}', [InvoiceController::class, 'invoicePDF'])->middleware('auth')->name('invoice-pdf');
Route::get('/addinvoice/{id}', [InvoiceController::class, 'create'])->middleware('auth')->name('addinvoices');
Route::post('/store-invoice', [InvoiceController::class, 'store'])->middleware('auth')->name('store-invoice');
Route::post('/edit-invoice/{id}', [InvoiceController::class, 'edit'])->middleware('auth')->name('edit-invoices');
Route::delete('/invoice-delete/{id}', [InvoiceController::class, 'destroy'])->middleware('auth')->name('invoice-delete');
Route::post('/invoice-update', [InvoiceController::class, 'update'])->middleware('auth')->name('invoice-update');
Route::get('/search-invoice', [InvoiceController::class, 'search'])->middleware('auth')->name('searchInvoice');

Route::post('/add-payment', [PaymentController::class, 'store'])->middleware('auth')->name('add-payment');
Route::delete('/delete-payment/{id}', [PaymentController::class, 'destroy'])->middleware('auth')->name('delete-payment');
Route::post('/edit-payment', [PaymentController::class, 'edit'])->middleware('auth')->name('edit-payment');
Route::post('/show-payment', [PaymentController::class, 'show'])->middleware('auth')->name('show-payment');
Route::post('/update-payment', [PaymentController::class, 'update'])->middleware('auth')->name('update-payment');

Route::get('/instructors', [InstructorController::class, 'index'])->middleware('auth')->name('instructors');
Route::get('/viewinstructor', [InstructorController::class, 'show'])->middleware('auth')->name('instructors');
Route::get('/addinstructor', [InstructorController::class, 'create'])->middleware('auth')->name('instructors');
Route::post('/storeinstructor', [InstructorController::class, 'store'])->middleware('auth')->name('instructors');
Route::post('/editinstructor/{id}', [InstructorController::class, 'edit'])->middleware('auth')->name('instructors');
Route::post('/updateinstructor', [InstructorController::class, 'update'])->middleware('auth')->name('instructors');
Route::delete('/deleteinstructor/{id}', [InstructorController::class, 'destroy'])->middleware('auth')->name('instructors');
Route::get('/instructor-search', [InstructorController::class, 'instructorSearch'])->name('instructorSearch');

Route::get('/administrators', [AdministratorController::class, 'index'])->middleware('auth')->name('adminitrators');
Route::get('/viewadministrator', [AdministratorController::class, 'show'])->middleware('auth')->name('viewadministrator');
Route::get('/addadministrator', [AdministratorController::class, 'create'])->middleware('auth')->name('addadministrator');
Route::post('/storeadministrator', [AdministratorController::class, 'store'])->middleware('auth')->name('storeadministrator');
Route::post('/editadministrator/{id}', [AdministratorController::class, 'edit'])->middleware('auth')->name('editadministrator');
Route::post('/updateadministrator', [AdministratorController::class, 'update'])->middleware('auth')->name('updateadministrator');
Route::delete('/deleteadministrator/{id}', [AdministratorController::class, 'destroy'])->middleware('auth')->name('deleteadministrator');

Route::get('/fleet', [FleetController::class, 'index'])->middleware('auth')->name('fleet');
Route::get('/viewfleet', [FleetController::class, 'show'])->middleware('auth')->name('viewfleet');
Route::get('/addfleet', [FleetController::class, 'create'])->middleware('auth')->name('addfleet');
Route::post('/storefleet', [FleetController::class, 'store'])->middleware('auth')->name('storefleet');
Route::post('/editfleet/{id}', [FleetController::class, 'edit'])->middleware('auth')->name('editfleet');
Route::post('/updatefleet', [FleetController::class, 'update'])->middleware('auth')->name('updatefleet');
Route::delete('/deletefleet/{id}', [FleetController::class, 'destroy'])->middleware('auth')->name('deletefleet');

Route::get('/settings', [SettingController::class, 'edit'])->middleware('auth')->name('settings');
Route::post('/settings-update', [SettingController::class, 'update'])->middleware('auth')->name('settings');
Route::post('/invoicesettings-update', [InvoiceSettingController::class, 'update'])->middleware('auth')->name('settings');

Route::get('/super-admin-profile', [InstructorController::class, 'show-super-admin'])->middleware('auth')->name('super-admin-profile');
Route::get('/instructor-profile', [Controller::class, 'show-profile'])->middleware('auth')->name('instructor-profile');
Route::get('/admin-profile', [AdminController::class, 'show'])->middleware('auth')->name('admin-profile');

Route::get('/expenses', [ExpenseController::class, 'index'])->middleware('auth')->name('expenses');
Route::get('/viewexpense', [ExpenseController::class, 'show'])->middleware('auth')->name('viewexpense');
Route::get('/addexpense', [ExpenseController::class, 'create'])->middleware('auth')->name('addexpense');
Route::post('/storeexpense', [ExpenseController::class, 'store'])->middleware('auth')->name('storeexpense');
Route::post('/editexpense/{id}', [ExpenseController::class, 'edit'])->middleware('auth')->name('editexpense');
Route::post('/updateexpense', [ExpenseController::class, 'update'])->middleware('auth')->name('updateexpense');
Route::delete('/deleteexpense/{id}', [ExpenseController::class, 'destroy'])->middleware('auth')->name('deleteexpense');


Route::get('/dashboard', [HomeController::class,'index'])->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

//Trial Routes

Route::view('/1', 'pdf_templates.aptitudeTestreferenceLetter');
Route::view('/2', 'pdf_templates.trafficCardreferenceLetter');
Route::view('/3', 'pdf_templates.secondAptitudeTestreferenceLetter');
Route::view('/4', 'pdf_templates.lessonReport');
Route::view('/5', 'pdf_templates.finalReferenceLetter');

Auth::routes();

Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
