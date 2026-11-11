<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
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
