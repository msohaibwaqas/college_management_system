<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\InstituteSettingsController;
use App\Http\Controllers\ColorSettingsController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\CommitteeController;
use App\Http\Controllers\PrincipalController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\BannerSlideController;

/*
|--------------------------------------------------------------------------
| Admin & Settings Routes
|--------------------------------------------------------------------------
*/

// User Management & Role Permissions
Route::middleware(['role:super_admin,admin,principal'])->group(function () {
    Route::resource('users', UserController::class);
    Route::post('/staff/{staff}/create-account', [UserController::class, 'createAccountForStaff'])->name('staff.create-account');
    Route::get('/role-permissions', [RolePermissionController::class, 'index'])->name('role-permissions.index');
    Route::post('/role-permissions', [RolePermissionController::class, 'update'])->name('role-permissions.update');
});

// Downloads Management Routes (Admin, Principal, Clerk only)
Route::prefix('admin/downloads')->middleware('role:admin,principal,clerk,bs_clerk,hssc_clerk')->group(function () {
    Route::get('/', [DownloadController::class, 'index'])->name('downloads.index');
    Route::get('/create', [DownloadController::class, 'create'])->name('downloads.create');
    Route::post('/', [DownloadController::class, 'store'])->name('downloads.store');
    Route::delete('/{id}', [DownloadController::class, 'destroy'])->name('downloads.destroy');
});

// Institute Settings Routes (Admin only)
Route::prefix('admin/settings')->middleware('role:admin')->group(function () {
    Route::get('/', [InstituteSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/', [InstituteSettingsController::class, 'update'])->name('settings.update');
    Route::post('/logo', [InstituteSettingsController::class, 'uploadLogo'])->name('settings.logo.upload');
    Route::post('/banner', [InstituteSettingsController::class, 'uploadBanner'])->name('settings.banner.upload');
    
    // Color Settings
    Route::get('/colors', [ColorSettingsController::class, 'index'])->name('settings.colors');
    Route::post('/colors', [ColorSettingsController::class, 'update'])->name('settings.colors.update');
    Route::post('/colors/reset', [ColorSettingsController::class, 'reset'])->name('settings.colors.reset');
    
    // Database Backup & Restore
    Route::get('/database/backup', [DatabaseBackupController::class, 'download'])->name('database.backup');
    Route::post('/database/restore', [DatabaseBackupController::class, 'restore'])->name('database.restore');

    // Banner Slides (multi-image hero)
    Route::post('/banner-slides', [BannerSlideController::class, 'store'])->name('banner-slides.store');
    Route::post('/banner-slides/{bannerSlide}/toggle', [BannerSlideController::class, 'toggleActive'])->name('banner-slides.toggle');
    Route::delete('/banner-slides/{bannerSlide}', [BannerSlideController::class, 'destroy'])->name('banner-slides.destroy');
    Route::post('/banner-slides/reorder', [BannerSlideController::class, 'reorder'])->name('banner-slides.reorder');
});

// Website Management Routes (Admin, Principal, Clerk)
Route::prefix('admin')->middleware('role:admin,principal,clerk')->group(function () {
    Route::resource('albums', AlbumController::class);
    Route::resource('updates', UpdateController::class);
    Route::resource('committees', CommitteeController::class);
    Route::resource('principals', PrincipalController::class);
    
    // Photo Management
    Route::post('albums/{album}/photos', [PhotoController::class, 'store'])->name('photos.store');
    Route::delete('photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
});
