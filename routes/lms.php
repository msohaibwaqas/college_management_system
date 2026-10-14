<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LmsController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| LMS & Student Portal Routes
|--------------------------------------------------------------------------
*/

Route::prefix('lms')->name('lms.')->group(function () {
    // Shared LMS Dashboard / Home
    Route::get('/dashboard', [LmsController::class, 'dashboard'])->name('dashboard');
    
    // Assignments
    Route::prefix('assignments')->name('assignments.')->group(function () {
        Route::get('/', [AssignmentController::class, 'index'])->name('index');
        Route::get('/create', [AssignmentController::class, 'create'])->name('create');
        Route::post('/', [AssignmentController::class, 'store'])->name('store');
        Route::get('/{assignment}', [AssignmentController::class, 'show'])->name('show');
        Route::get('/{assignment}/edit', [AssignmentController::class, 'edit'])->name('edit');
        Route::put('/{assignment}', [AssignmentController::class, 'update'])->name('update');
        Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->name('destroy');
        
        // Submissions
        Route::post('/{assignment}/submit', [AssignmentController::class, 'submit'])->name('submit');
        Route::get('/submissions/{submission}', [AssignmentController::class, 'showSubmission'])->name('submissions.show');
        Route::post('/submissions/{submission}/grade', [AssignmentController::class, 'grade'])->name('grade');
    });

    // Chat / Messenger
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/{recipient}', [ChatController::class, 'show'])->name('show');
        Route::post('/{recipient}/send', [ChatController::class, 'send'])->name('send');
    });

    // Student Specific
    Route::get('/my-performance', [LmsController::class, 'performance'])->name('performance');
});
