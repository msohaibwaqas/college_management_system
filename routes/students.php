<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\StudentPromotionController;
use App\Http\Controllers\BiseRollNumberController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Student Management Routes
|--------------------------------------------------------------------------
*/

Route::prefix('students')->group(function () {
    Route::get('/import', [StudentImportController::class, 'index'])->name('students.import');
    Route::get('/import/sample', [StudentImportController::class, 'downloadSample'])->name('students.import.sample');
    Route::get('/import/sample-he', [StudentImportController::class, 'downloadSampleHe'])->name('students.import.sample.he');
    Route::post('/import', [StudentImportController::class, 'store'])->name('students.import.store');
    Route::get('/import/preview', [StudentImportController::class, 'preview'])->name('students.import.preview');
    Route::post('/import/confirm', [StudentImportController::class, 'confirmImport'])->name('students.import.confirm');
    Route::get('/import/download-validated', [StudentImportController::class, 'downloadValidated'])->name('students.import.download-validated');
    Route::get('/export', [StudentController::class, 'export'])->name('students.export');
    Route::get('/by-subject', [StudentController::class, 'studentsBySubject'])->name('students.by_subject');
    Route::get('/groups', [StudentController::class, 'groupStudents'])->name('students.groups');
    Route::get('/groups/export/{year}', [StudentController::class, 'exportGroupsCSV'])->name('students.groups.export');
    Route::post('/bulk-delete', [StudentController::class, 'bulkDelete'])->name('students.bulk-delete');
    Route::post('/bulk-status-update', [StudentController::class, 'bulkStatusUpdate'])->name('students.bulk-status-update');
    Route::post('/bulk-promote', [StudentController::class, 'bulkPromote'])->name('students.bulk-promote');
    Route::get('/promotion', [StudentPromotionController::class, 'index'])->name('students.promotion');
    Route::post('/promotion/update', [StudentPromotionController::class, 'update'])->name('students.promotion.update');
    
    // BISE Roll Numbers
    Route::get('/bise-roll-numbers', [BiseRollNumberController::class, 'index'])->name('bise-roll-numbers.index');
    Route::post('/bise-roll-numbers/update', [BiseRollNumberController::class, 'update'])->name('bise-roll-numbers.update');
    
    // Student Trash & Restore
    Route::get('/trash', [StudentController::class, 'trash'])->name('students.trash');
    Route::post('/{id}/restore', [StudentController::class, 'restore'])->name('students.restore');
    Route::post('/trash/empty', [StudentController::class, 'emptyTrash'])->name('students.trash.empty');
    Route::delete('/{id}/force-delete', [StudentController::class, 'forceDelete'])->name('students.force-delete');
    
    // User Management for Students
    Route::post('/users/student/{student}/create', [UserController::class, 'createAccountForStudent'])->name('users.student.create');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset_password');
    Route::post('/users/students/bulk-create', [UserController::class, 'bulkCreateStudentAccounts'])->name('users.students.bulk-create');

    Route::get('/', [StudentController::class, 'index'])->name('students.index');
    Route::get('/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/', [StudentController::class, 'store'])->name('students.store');
    Route::get('/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::get('/{student}/change-subjects', [StudentController::class, 'changeSubjects'])->name('students.change-subjects');
    Route::post('/{student}/update-subjects', [StudentController::class, 'updateSubjects'])->name('students.update-subjects');
    Route::get('/vouchers/{voucher}/download', [StudentController::class, 'downloadVoucher'])->name('students.vouchers.download');
    Route::post('/vouchers/{voucher}/confirm-payment', [StudentController::class, 'confirmVoucherPayment'])->name('students.vouchers.confirm-payment');
    Route::delete('/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
})->middleware('role:admin,principal,clerk,hssc_clerk,hssc_admin,bs_clerk,bs_admin');
