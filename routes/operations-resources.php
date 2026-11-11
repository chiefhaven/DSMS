<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BulkAttendanceController;
use App\Http\Controllers\ScheduleLessonController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\InvoiceSettingController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\knowledgeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('attendances', AttendanceController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::get('students/{student}/attendances/create', [AttendanceController::class, 'create'])
        ->name('students.attendances.create');

    Route::resource('bulk-attendances', BulkAttendanceController::class)
        ->only(['create', 'edit']);

    Route::resource('schedules', ScheduleLessonController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::get('schedules/data', [ScheduleLessonController::class, 'getLessonSchedules'])
        ->name('schedules.data');
    Route::patch('schedules/{schedule}/restore', [ScheduleLessonController::class, 'restore'])
        ->name('schedules.restore');

    Route::singleton('settings', SettingController::class)->only(['edit', 'update']);
    Route::singleton('invoice-settings', InvoiceSettingController::class)->only(['update']);
    Route::resource('announcements', AnnouncementController::class)->only(['create']);
    Route::resource('roles', RoleController::class)->only(['index']);
    Route::resource('knowledge', knowledgeController::class)->only(['index']);
});
