<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\FleetUpdateSubmissionController;
use App\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('my-vehicle', [FleetUpdateSubmissionController::class, 'index'])
        ->name('my-vehicle');
    Route::get('my-vehicle/{fleet}/locations', [FleetUpdateSubmissionController::class, 'locations'])
        ->name('my-vehicle.locations');
    Route::post('my-vehicle/{fleet}', [FleetUpdateSubmissionController::class, 'store'])
        ->name('my-vehicle.store');
    Route::get('my-fleet-updates', fn () => redirect()->route('my-vehicle', [], 301));
    Route::post('my-fleet-updates/{fleet}', [FleetUpdateSubmissionController::class, 'store']);
    Route::get('fleet-update-approvals', [FleetUpdateSubmissionController::class, 'approvals'])
        ->name('fleet-update-approvals.index');
    Route::get('fleet-update-approvals/{submission}/evidence/{field}', [FleetUpdateSubmissionController::class, 'evidence'])
        ->name('fleet-update-approvals.evidence');
    Route::post('fleet-update-approvals/{submission}/approve', [FleetUpdateSubmissionController::class, 'approve'])
        ->name('fleet-update-approvals.approve');
    Route::post('fleet-update-approvals/{submission}/reject', [FleetUpdateSubmissionController::class, 'reject'])
        ->name('fleet-update-approvals.reject');

    // CourseController::show remains the legacy JSON details endpoint.
    Route::get('courses/{course}', [CourseController::class, 'showPage'])
        ->where('course', '(?!create$|edit$)[^/]+')
        ->name('courses.show');
    Route::resource('courses', CourseController::class)
        ->except('show')
        ->parameters(['courses' => 'course']);
    // Lesson show and classroom form actions are stubs; working classroom forms use modals.
    Route::resource('lessons', LessonController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['lessons' => 'lesson']);
    Route::resource('classrooms', ClassroomController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['classrooms' => 'classroom'])
        ->where(['classroom' => '(?!create$|edit$)[^/]+']);
    Route::resource('fleet', FleetController::class)
        ->parameters(['fleet' => 'fleet'])
        ->where(['fleet' => '(?!create$|edit$)[^/]+']);
});
