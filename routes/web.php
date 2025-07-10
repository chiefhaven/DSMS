<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\InvoiceSettingController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpensePaymentController;
use App\Http\Controllers\havenUtils;
use App\Http\Controllers\InstructorPaymentController;
use App\Http\Controllers\knowledgeController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoleDispatcherController;
use App\Http\Controllers\ScheduleLessonController;
use App\Http\Controllers\VehicleTrackerController;
use App\Models\Announcement;
use App\Models\expense;
use App\Models\knowledge;
use App\Models\ScheduleLesson;
use App\Models\VehicleTracker;
use Illuminate\Support\Facades\Artisan;

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

Route::post('/', [HomeController::class,'index'])->middleware(['auth'])->name('dashboard');
Route::get('/summaryData', [HomeController::class,'summaryData'])->middleware(['auth'])->name('summaryData');
Route::get('/instructorSummaryData', [HomeController::class,'instructorSummaryData'])->middleware(['auth'])->name('instructorSummaryData');
Route::get('/', [HomeController::class,'index'])->middleware(['auth'])->name('dashboard');

Auth::routes();

Route::post('/send-balance-sms/{student}/{type}', [NotificationController::class, 'balanceSMS'])->middleware('auth')->name('send-balance-sms');
Route::get('/sms-templates', [NotificationTemplateController::class, 'create'])->middleware('auth')->name('sms_templates');
Route::post('/update-notification-templates/{template}', [NotificationTemplateController::class, 'update'])->middleware('auth')->name('update_notification_templates');


Route::get('/students', [StudentController::class, 'index'])->middleware('auth')->name('students');
Route::get('/finished-students', [StudentController::class, 'index'])->middleware('auth')->name('students');
Route::get('/viewstudent/{id}', [StudentController::class, 'show'])->middleware('auth')->name('viewStudent');
Route::get('/addstudent', [StudentController::class, 'create'])->middleware('auth')->name('addstudent');
Route::post('/storestudent', [StudentController::class, 'store'])->middleware('auth')->name('storestudents');
Route::get('/edit-student/{id}', [StudentController::class, 'edit'])->middleware('auth')->name('editstudent');
Route::delete('/student-delete/{id}', [StudentController::class, 'destroy'])->middleware('auth')->name('students-delete');
Route::post('/student-update', [StudentController::class, 'update'])->middleware('auth')->name('editstudent');
Route::post('/trafic-card-reference-letter/{id}', [StudentController::class, 'trafficCardReferenceLetter'])->middleware('auth')->name('student_traffic_card');
Route::post('/aptitude-test-reference-letter/{id}', [StudentController::class, 'aptitudeTestReferenceLetter'])->middleware('auth')->name('student_aptitude_test');
Route::post('/second-aptitude-test-reference-letter/{id}', [StudentController::class, 'secondAptitudeTestReferenceLetter'])->middleware('auth')->name('students_final_aptitude');
Route::post('/final-test-reference-letter/{id}', [StudentController::class, 'finalReferenceLetter'])->middleware('auth')->name('final-test-report');
Route::post('/lesson-report/{id}', [StudentController::class, 'lessonReport'])->middleware('auth')->name('lesson-report');
Route::get('/search-student', [StudentController::class, 'search'])->middleware('auth')->name('searchStudent');
Route::post('/studentsPdf', [StudentController::class, 'studentsPDF'])->middleware('auth')->name('studentPDF');
Route::post('/assignCar', [StudentController::class, 'assignCar'])->middleware('auth')->name('assignCar');
Route::post('/unAssignCar', [StudentController::class, 'unAssignCar'])->middleware('auth')->name('unAssign');
Route::post('/updateStudentStatus/{student}', [StudentController::class, 'updateStudentStatus'])->middleware('auth')->name('updateStudentStatus');
Route::post('/assign-class-room', [StudentController::class, 'assignClassRoom'])->middleware('auth')->name('student-assign-class-room');

Route::get('/attendances', [AttendanceController::class, 'index'])->middleware('auth')->name('attendances');
Route::post('/addattendance/{token}', [AttendanceController::class, 'create'])->middleware('auth')->name('addattendance');

Route::get("/add-bulk-attendance", function(){
    return view("attendances.addBulkAttendance");
 })->middleware('auth');

Route::get("/bulk-attendances", function(){
    return view("attendances.bulkAttendances");
})->middleware('auth');

Route::post('/storeattendance', [AttendanceController::class, 'store'])->middleware('auth')->name('storeattendance');
Route::post('/editattendance/{id}', [AttendanceController::class, 'edit'])->middleware('auth')->name('editattendance');
Route::post('/updateattendance', [AttendanceController::class, 'update'])->middleware('auth')->name('updateattendance');
Route::delete('/deleteattendance/{id}', [AttendanceController::class, 'destroy'])->middleware('auth')->name('deleteattendance');
Route::get('/attendance-student-search', [AttendanceController::class, 'autocompletestudentSearch'])->middleware('auth')->name('attendance-student-search');
Route::get('/attendanceSummary/{id}', [AttendanceController::class, 'attendanceSummary'])->middleware('auth')->name('attendanceSummary');

Route::get('/schedules', [ScheduleLessonController::class, 'schedules'])->middleware('auth')->name('schedules');
Route::get('/schedule-lesson-index', [ScheduleLessonController::class, 'index'])->middleware('auth')->name('schedulelesson-index');
Route::get('/schedule-lessons', [ScheduleLessonController::class, 'getLessonSchedules'])->middleware('auth')->name('schedulelesson');
Route::post('/store-lesson-schedule', [ScheduleLessonController::class, 'store'])->middleware('auth')->name('storeschedulelesson');
Route::put('/update-lesson-schedule/{id}', [ScheduleLessonController::class, 'update'])->middleware('auth')->name('updateschedulelesson');
Route::delete('schedule-lesson/{id}', [ScheduleLessonController::class, 'destroy'])->middleware('auth')->name('destroyschedulelesson');
Route::post('/checkStudentSchedule', [ScheduleLessonController::class, 'checkStudent'])->middleware('auth')->name('schedukecheckStudent');
Route::patch('/schedule-lesson/{id}/restore', [ScheduleLessonController::class, 'restore']);

Route::get('/courses', [CourseController::class, 'index'])->middleware('auth')->name('courses');
Route::get('/view-course/{id}', function ($courseId) {
    return view('courses.viewcourse', compact('courseId'));
})->middleware('auth')->name('view-course');
Route::get('/course-details/{id}', [CourseController::class, 'show'])->middleware('auth')->name('courseDetails');
Route::get('/addcourse', [CourseController::class, 'create'])->middleware('auth')->name('addcourse');
Route::post('/storecourse', [CourseController::class, 'store'])->middleware('auth')->name('editcourse');
Route::get('/edit-course/{id}', [CourseController::class, 'edit'])->middleware('auth')->name('edit-course');
Route::delete('/delete-course/{id}', [CourseController::class, 'destroy'])->middleware('auth')->name('courses');
Route::put('/updatecourse', [CourseController::class, 'update'])->middleware('auth')->name('update-courses');
Route::put('/update-course-lesson', [CourseController::class, 'updateCourseLessons'])->middleware('auth')->name('update-course-lessons');

Route::get('/lessons', [LessonController::class, 'index'])->middleware('auth')->name('lessons');
Route::get('/student-lessons/{student}', [LessonController::class, 'studentLessons'])->middleware('auth')->name('studentLessons');
Route::get('/getLessons', [LessonController::class, 'getLessons'])->middleware('auth')->name('getLessons');
Route::get('/view-lesson/{id}', [LessonController::class, 'show'])->middleware('auth')->name('viewlessons');
Route::get('/addlesson', [LessonController::class, 'create'])->middleware('auth')->name('addlessons');
Route::post('/storelesson', [LessonController::class, 'store'])->middleware('auth')->name('editlessons');
Route::post('/edit-lesson/{id}', [LessonController::class, 'edit'])->middleware('auth')->name('edit_lessons');
Route::delete('/delete-lesson/{id}', [LessonController::class, 'destroy'])->middleware('auth')->name('delete_lessons');
Route::put('/updatelesson/{id}', [LessonController::class, 'update'])->middleware('auth')->name('updatelesson');

Route::get('/classes', [ClassroomController::class, 'index'])->middleware('auth')->name('classrooms');
Route::get('/getClassRooms', [ClassroomController::class, 'getClassrooms'])->middleware('auth')->name('getClassrooms');
Route::get('/view-classroom/{id}', [ClassroomController::class, 'show'])->middleware('auth')->name('view_classrooms');
Route::get('/addclassroom', [ClassroomController::class, 'create'])->middleware('auth')->name('add_classrooms');
Route::post('/storeclassroom', [ClassroomController::class, 'store'])->middleware('auth')->name('store_classrooms');
Route::post('/edit-classroom/{id}', [ClassroomController::class, 'edit'])->middleware('auth')->name('edit_classrooms');
Route::delete('/delete-classroom/{id}', [ClassroomController::class, 'destroy'])->middleware('auth')->name('delete_classrooms');
Route::put('/updateclassroom/{id}', [ClassroomController::class, 'update'])->middleware('auth')->name('update_classrooms');

Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('auth')->name('invoices');
Route::get('/view-invoice/{id}', [InvoiceController::class, 'show'])->middleware('auth')->name('view-invoice');
Route::get('/invoice-pdf/{id}', [InvoiceController::class, 'invoicePDF'])->middleware('auth')->name('invoice-pdf');
Route::get('/addinvoice/{id}', [InvoiceController::class, 'create'])->middleware('auth')->name('addinvoices');
Route::post('/store-invoice', [InvoiceController::class, 'store'])->middleware('auth')->name('store-invoice');
Route::get('/edit-invoice/{id}', [InvoiceController::class, 'edit'])->middleware('auth')->name('edit-invoices');
Route::delete('/invoice-delete/{invoice}', [InvoiceController::class, 'destroy'])->middleware('auth')->name('invoice-delete');
Route::post('/invoice-update', [InvoiceController::class, 'update'])->middleware('auth')->name('invoice-update');
Route::get('/search-invoice', [InvoiceController::class, 'search'])->middleware('auth')->name('searchInvoice');

Route::get('/payments', [PaymentController::class, 'index'])->middleware('auth')->name('payments');
Route::post('/add-payment', [PaymentController::class, 'store'])->middleware('auth')->name('add-payment');
Route::delete('/delete-payment/{id}', [PaymentController::class, 'destroy'])->middleware('auth')->name('delete-payment');
Route::post('/edit-payment', [PaymentController::class, 'edit'])->middleware('auth')->name('edit-payment');
Route::post('/show-payment', [PaymentController::class, 'show'])->middleware('auth')->name('show-payment');
Route::post('/update-payment', [PaymentController::class, 'update'])->middleware('auth')->name('update-payment');

Route::middleware('auth')->group(function () {
    Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors');
    Route::get('/instructors-json', [InstructorController::class, 'indexInstructors'])->name('instructors-json');
    Route::get('/addinstructor', [InstructorController::class, 'create'])->name('addinstructor');
    Route::post('/storeinstructor', [InstructorController::class, 'store'])->name('storeinstructor');
    Route::get('/editinstructor/{id}', [InstructorController::class, 'edit'])->name('editinstructor');
    Route::post('/updateinstructor', [InstructorController::class, 'update'])->name('updateinstructor');
    Route::delete('/deleteinstructor/{id}', [InstructorController::class, 'destroy'])->name('deleteinstructor');
});

// Routes without auth middleware
Route::get('/viewinstructor/{instructor}', [InstructorController::class, 'show'])->name('viewinstructor');
Route::get('/instructor-search', [InstructorController::class, 'instructorSearch'])->name('instructorSearch');

Route::get('/instructor-payments', [InstructorPaymentController::class, 'index'])->middleware('auth')->name('instructor-payments');
Route::get('/fetchPayments', [InstructorPaymentController::class, 'fetchPayments'])->middleware('auth')->name('fetchPayments');
Route::get('/instructors/payment/{payment}', [InstructorPaymentController::class, 'show'])->name('viewpayment');
Route::get('/instructors/payment/edit/{payment}', [InstructorPaymentController::class, 'edit'])->middleware('auth')->name('editpayment');
Route::post('/instructors/payment/update/{payment}', [InstructorPaymentController::class, 'update'])->middleware('auth')->name('updatepayment');
Route::delete('/instructors/payment/{payment}', [InstructorPaymentController::class, 'destroy'])->middleware('auth')->name('deletepayment');

Route::get('/instructor-data/{instructor}', [InstructorController::class, 'instructorData'])->middleware('auth')->name('instructor-students');

Route::get('/administrators', [AdministratorController::class, 'index'])->middleware('auth')->name('adminitrators');
Route::get('/viewadministrator', [AdministratorController::class, 'show'])->middleware('auth')->name('viewadministrator');
Route::get('/addadministrator', [AdministratorController::class, 'create'])->middleware('auth')->name('addadministrator');
Route::post('/storeadministrator', [AdministratorController::class, 'store'])->middleware('auth')->name('storeadministrator');
Route::get('/editadministrator/{id}', [AdministratorController::class, 'edit'])->middleware('auth')->name('editadministrator');
Route::post('/updateadministrator', [AdministratorController::class, 'update'])->middleware('auth')->name('updateadministrator');
Route::delete('/deleteadministrator/{id}', [AdministratorController::class, 'destroy'])->middleware('auth')->name('deleteadministrator');

Route::get('/fleet', [FleetController::class, 'index'])->middleware('auth')->name('fleet.index');
Route::get('/getFleet', [FleetController::class, 'getFleet'])->middleware('auth')->name('getFleet');
Route::get('/view-fleet/{fleet}', [FleetController::class, 'show'])->middleware('auth')->name('view-fleet');
Route::get('/addfleet', [FleetController::class, 'create'])->middleware('auth')->name('addfleet');
Route::post('/storefleet', [FleetController::class, 'store'])->middleware('auth')->name('storefleet');
Route::get('/editfleet/{id}', [FleetController::class, 'edit'])->middleware('auth')->name('editfleet');
Route::post('/updatefleet', [FleetController::class, 'update'])->middleware('auth')->name('updatefleet');
Route::delete('/deletefleet/{id}', [FleetController::class, 'destroy'])->middleware('auth')->name('deletefleet');

Route::get("/track-fleet", function(){
    return view("fleet.track-fleet");
 })->middleware('auth');


Route::get('/settings', [SettingController::class, 'edit'])->middleware('auth')->name('settings');
Route::post('/settings-update', [SettingController::class, 'update'])->middleware('auth')->name('settings-update');
Route::post('/attendance-time-update', [SettingController::class, 'attendanceTimeUpdate'])->middleware('auth')->name('attendance-time-update');
Route::post('/invoicesettings-update', [InvoiceSettingController::class, 'update'])->middleware('auth')->name('invoicesettings-update');

Route::get('/super-admin-profile', [InstructorController::class, 'show-super-admin'])->middleware('auth')->name('super-admin-profile');

Route::get('/expenses', [ExpenseController::class, 'index'])->middleware('auth')->name('expenses');

Route::get('/view-expense/{expense}', function (Expense $expense) {
    return view('expenses.viewExpense', compact('expense'));
})->middleware('auth');

Route::get('/addexpense', [ExpenseController::class, 'create'])->middleware('auth')->name('expenses.index');
Route::post('/storeexpense', [ExpenseController::class, 'store'])->middleware('auth')->name('expenses.store');
Route::post('/updateExpense', [ExpenseController::class, 'update'])->middleware('auth')->name('updateExpense');
Route::get('/editexpense/{expense}', [ExpenseController::class, 'edit'])->middleware('auth')->name('editexpense');
Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->middleware('auth')->name('delete-expense');
Route::get('/expensedownload/{expense}', [ExpenseController::class, 'download'])->middleware('auth')->name('downloadexpense');
Route::get('/search-expense', [ExpenseController::class, 'searchExpense'])->middleware('auth')->name('searchExpense');
Route::get('/expense-student-search', [ExpenseController::class, 'autocompletestudentSearch'])->middleware('auth')->name('expense-student-search');
Route::get('/review-expense/{expense}', [ExpenseController::class, 'reviewExpense'])->middleware('auth')->name('reviewExpense');
Route::get('/reviewExpenseData/{expense}', [ExpenseController::class, 'reviewExpenseData'])->middleware('auth')->name('reviewExpenseData');
Route::post('/removeStudent', [ExpenseController::class, 'removeStudent'])->middleware('auth')->name('removeStudent');
Route::post('/checkStudent', [ExpenseController::class, 'checkStudent'])->middleware('auth')->name('checkStudent');
Route::post('/approveList', [ExpenseController::class, 'approveList'])->middleware('auth')->name('approveList');
Route::get('/expenses/pay/{student}/{expense}', [ExpensePaymentController::class, 'store'])->middleware('auth')->name('expense.pay');

Route::get("/expense-payments", function(){
    return view("expenses.expensePaymentsList");
 })->middleware('auth');

Route::get("/expense-types", function(){
    return view("expenses.expenseTypes");
})->middleware('auth');


Route::get('/expense-payment-receipt/{id}', [ExpenseController::class, 'downloadExpensePaymentReceipt'])->middleware('auth')
->name('expense-payments.download-receipt');

Route::get('/expense-payment-report/{expense}', [ExpenseController::class, 'expensePaymentReport'])->middleware('auth')->name('expense-payment-report');


Route::get('/dashboard', [HomeController::class,'index'])->middleware(['auth'])->name('dashboard');

//Announcement routes
Route::get('/send-announcement', [AnnouncementController::class,'create'])->middleware(['auth'])->name('send-announcement');
Route::post('/sendAnnouncement', [AnnouncementController::class,'send'])->middleware(['auth'])->name('sendAnnouncement');
Route::post('/get-balance-template', [AnnouncementController::class,'getBalanceTempplate'])->middleware(['auth'])->name('getBalanceTemplate');

require __DIR__.'/auth.php';


Auth::routes();

//qrCode routes
// Route::get('/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/{token}', [AttendanceController::class, 'create'])
//     ->middleware('redirectIfUnauthenticated')
//     ->name('attendanceQrCode');

Route::get('/e8704ed2-d90e-41ca-9143-ceb2bb517cc7/{token}', [RoleDispatcherController::class, 'handle'])
    ->middleware('redirectIfUnauthenticated')
    ->name('attendanceQrCode');

Route::get('/docs/{token}', [InvoiceController::class, 'unauthenticatedQrScan'])
    ->name('docsQrCode');

Route::get('/e8704ed2-d90e-41ca-9143/{token}', [InvoiceController::class, 'unauthenticatedInvoiceScan'])
    ->name('invoiceQrCode');

Route::get("/scanqrcode", function(){
    return view("qrCodeScanner");
 })->middleware('auth');

 Route::get('/migrate', function () {
    // Check that the environment is not production before running the migration
    if (app()->environment('production')) {
        abort(403, 'Unauthorized action.');
    }

    // Ensure the user has an appropriate role or permission (e.g., 'admin')
    if (!auth()->user()->hasRole('superAdmin')) {
        abort(403, 'Unauthorized action.');
    }

    // Run the migration with the '--force' flag
    Artisan::call('migrate', ['--force' => true]);
    return response()->json(['message' => 'Database migration completed successfully!']);
})->middleware(['auth']);

Route::get('/lesson-search', [havenUtils::class, 'autocompleteLessonSearch'])->middleware('auth')->name('lesson-search');
Route::post('/check-class-fleet-assignment', [havenUtils::class, 'checkInstructorClassFleetAssignment'])->middleware('auth')->name('check-class-fleet-assignment');

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('auth');
Route::get('/load-notifications', [NotificationController::class, 'loadNotifications'])->name('notifications.loadNotications')->middleware('auth');
Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read')->middleware('auth');
//Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read')->middleware('auth');
Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead')->middleware('auth');

Route::get('/knowledge', [knowledgeController::class, 'index'])->middleware('auth')->name('knolwedge')->middleware('auth');

Route::get('/roles', [RoleController::class, 'index'])->middleware('auth')->name('roles');

Route::get("/scan-to-pay", function(){
    return view("expenses.scanToPay");
 })->middleware('auth');

Route::get("/driving-license-classes", function(){
    return view("licenceClasses.licenceClasses");
})->middleware('auth');
