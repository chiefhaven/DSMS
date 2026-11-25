<?php

use App\Http\Controllers\AdminStudentTicketController;
use App\Http\Controllers\StudentTicketAttachmentController;
use App\Http\Controllers\StudentTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('student')->name('student.')->group(function () {
    Route::get('/tickets', [StudentTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [StudentTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [StudentTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [StudentTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/replies', [StudentTicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/messages/{message}/vote', [StudentTicketController::class, 'vote'])->name('tickets.messages.vote');
});

Route::middleware(['auth', 'permission:view all student tickets|manage student tickets'])
    ->prefix('admin/student-tickets')
    ->name('admin.tickets.')
    ->group(function () {
        Route::get('/', [AdminStudentTicketController::class, 'index'])->name('index');
        Route::get('/{ticket}', [AdminStudentTicketController::class, 'show'])->name('show');
        Route::patch('/{ticket}', [AdminStudentTicketController::class, 'update'])->name('update');
        Route::post('/{ticket}/replies', [AdminStudentTicketController::class, 'reply'])->name('reply');
    });

Route::get('/student-tickets/attachments/{attachment}', [StudentTicketAttachmentController::class, 'download'])
    ->middleware('auth')
    ->name('tickets.attachments.download');
