<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\PublicHolidayController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\StaffAttendanceReportController;
use App\Http\Controllers\SalaryStructureController;
use App\Http\Controllers\PayrollController;

/*
|--------------------------------------------------------------------------
| HR & Payroll Routes
|--------------------------------------------------------------------------
*/

Route::prefix('hr')->middleware('role:admin,super_admin,principal,hssc_admin,bs_admin')->group(function () {
    Route::resource('leave-types', LeaveTypeController::class)->except(['show']);
    Route::resource('holidays', PublicHolidayController::class)->except(['show']);
    Route::get('attendance', [StaffAttendanceController::class, 'index'])->name('staff.attendance.index');
    Route::post('attendance', [StaffAttendanceController::class, 'store'])->name('staff.attendance.store');
    
    // Workflow Approvals
    Route::get('leave-approvals', [LeaveApprovalController::class, 'index'])->name('leave-approvals.index');
    Route::get('leave-approvals/{id}', [LeaveApprovalController::class, 'show'])->name('leave-approvals.show');
    Route::put('leave-approvals/{id}', [LeaveApprovalController::class, 'updateStatus'])->name('leave-approvals.update');
    
    // HR Reports
    Route::get('reports/attendance', [StaffAttendanceReportController::class, 'index'])->name('reports.staff-attendance.index');
    Route::post('reports/attendance', [StaffAttendanceReportController::class, 'generate'])->name('reports.staff-attendance.generate');

    // Payroll & Salary Management
    Route::resource('salary-structures', SalaryStructureController::class);
    Route::post('salary-structures/{id}/components', [SalaryStructureController::class, 'addComponent'])->name('salary-structures.add-component');
    Route::delete('salary-components/{id}', [SalaryStructureController::class, 'removeComponent'])->name('salary-structures.remove-component');

    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
    Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('payroll/{id}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::put('payroll/{id}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
});
