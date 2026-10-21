<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PublicAdmissionController;
use App\Http\Controllers\DownloadController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/storage/{path}', [PublicController::class, 'serveImage'])->where('path', '.*')->name('public.storage');


Route::get('/faculty', [PublicController::class, 'staff'])->name('public.staff');
Route::get('/programms', [PublicController::class, 'programs'])->name('public.programs');
Route::get('/department/{subject}', [PublicController::class, 'department'])->name('public.department');
Route::get('/downloads', [PublicController::class, 'downloads'])->name('public.downloads');
Route::get('/datesheets', [PublicController::class, 'datesheets'])->name('public.datesheets');
Route::get('/download/{id}', [DownloadController::class, 'download'])->name('download.file');
Route::get('/gallery', [PublicController::class, 'gallery'])->name('public.gallery');
Route::get('/gallery/{id}', [PublicController::class, 'album'])->name('public.album');
Route::get('/news', [PublicController::class, 'news'])->name('public.news');
Route::get('/principals', [PublicController::class, 'principals'])->name('public.principals');
Route::get('/principals/{id}', [PublicController::class, 'principalShow'])->name('public.principal.show');
Route::get('/students-corner', [PublicController::class, 'studentsCorner'])->name('public.students-corner');
Route::match(['get', 'post'], '/students-corner/search', [PublicController::class, 'searchStudent'])->name('public.students.search')->middleware('throttle:10,1');
Route::get('/students-corner/vouchers/{voucher}/download', [PublicController::class, 'downloadStudentVoucher'])->name('public.students.voucher.download');
Route::get('/students-corner/fines/{fine}/voucher', [PublicController::class, 'downloadFineVoucher'])->name('public.students.fine.voucher');
Route::get('/about', [PublicController::class, 'about'])->name('public.about');
Route::get('/contact', [PublicController::class, 'contact'])->name('public.contact');

// Online Admission Public Routes
Route::get('/apply', [PublicAdmissionController::class, 'showForm'])->name('apply.form');
Route::get('/apply/compulsory-subjects', [PublicAdmissionController::class, 'getCompulsorySubjects'])->name('apply.compulsory-subjects');
Route::post('/apply', [PublicAdmissionController::class, 'store'])->name('apply.store');
Route::match(['get', 'post'], '/apply/status', [PublicAdmissionController::class, 'checkStatus'])->name('apply.status');
Route::get('/apply/{application}/edit', [PublicAdmissionController::class, 'edit'])->name('apply.edit');
Route::put('/apply/{application}', [PublicAdmissionController::class, 'update'])->name('apply.update');
Route::post('/apply/{application}/upload-voucher', [PublicAdmissionController::class, 'uploadPaidVoucher'])->name('apply.upload-voucher');
Route::get('/apply/{application}/download-voucher', [PublicAdmissionController::class, 'downloadVoucher'])->name('apply.download-voucher');
