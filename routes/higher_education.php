<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\BsSubjectController;
use App\Http\Controllers\HeAdmissionController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\HeTeacherAssignmentController;
use App\Http\Controllers\HeDatesheetController;
use App\Http\Controllers\HeRollNumberController;
use App\Http\Controllers\HeSeatingPlanController;
use App\Http\Controllers\HeTimetableController;
use App\Http\Controllers\HeInvigilatorController;
use App\Http\Controllers\HeReportController;
use App\Http\Controllers\HeResultController;
use App\Http\Controllers\HeAttendanceController;

/*
|--------------------------------------------------------------------------
| Higher Education (BS / ADP) Routes
|--------------------------------------------------------------------------
*/

Route::prefix('he')->middleware('role:admin,principal,clerk,bs_clerk,bs_admin,teacher')->group(function () {
    
    // Administrative HE Routes (Restrict from Teachers)
    Route::middleware(['role:admin,principal,clerk,bs_clerk,bs_admin'])->group(function () {
        Route::resource('programs', ProgramController::class);
        Route::resource('bs-subjects', BsSubjectController::class);
        Route::get('admissions/search-student', [HeAdmissionController::class, 'searchStudent'])->name('admissions.search-student');
        Route::get('admissions/export', [HeAdmissionController::class, 'export'])->name('admissions.export');
        Route::post('admissions/bulk-delete', [HeAdmissionController::class, 'bulkDelete'])->name('admissions.bulk-delete');
        Route::post('admissions/bulk-status-update', [HeAdmissionController::class, 'bulkStatusUpdate'])->name('admissions.bulk-status-update');
        Route::post('admissions/bulk-promote', [HeAdmissionController::class, 'bulkPromote'])->name('admissions.bulk-promote');
        Route::resource('admissions', HeAdmissionController::class);
        Route::get('semesters/available-subjects', [SemesterController::class, 'availableSubjects'])->name('semesters.available-subjects');
        Route::resource('semesters', SemesterController::class);
        
        // HE Teacher Assignments
        Route::prefix('teacher-assignment')->group(function () {
            Route::get('/', [HeTeacherAssignmentController::class, 'index'])->name('he.teacher_assignment');
            Route::get('/get-subjects', [HeTeacherAssignmentController::class, 'getSubjects'])->name('he.teacher_assignment.get_subjects');
            Route::get('/get-teachers', [HeTeacherAssignmentController::class, 'getTeachers'])->name('he.teacher_assignment.get_teachers');
            Route::get('/unassigned-students', [HeTeacherAssignmentController::class, 'getUnassignedStudents'])->name('he.teacher_assignment.unassigned_students');
            Route::get('/assigned-students', [HeTeacherAssignmentController::class, 'getAssignedStudents'])->name('he.teacher_assignment.assigned_students');
            Route::post('/assign', [HeTeacherAssignmentController::class, 'assignStudents'])->name('he.teacher_assignment.assign_students');
            Route::post('/unassign', [HeTeacherAssignmentController::class, 'unassignStudents'])->name('he.teacher_assignment.unassign_students');
        });

        // HE Datesheets
        Route::prefix('datesheets')->group(function () {
            Route::get('/', [HeDatesheetController::class, 'index'])->name('he.datesheets.index');
            Route::get('/create', [HeDatesheetController::class, 'create'])->name('he.datesheets.create');
            Route::post('/', [HeDatesheetController::class, 'store'])->name('he.datesheets.store');
            Route::get('/generate', [HeDatesheetController::class, 'generate'])->name('he.datesheets.generate');
            Route::post('/generate', [HeDatesheetController::class, 'storeGenerated'])->name('he.datesheets.store_generated');
            Route::get('/summary', [HeDatesheetController::class, 'summary'])->name('he.datesheets.summary');
            Route::get('/get-subjects', [HeDatesheetController::class, 'getSubjects'])->name('he.datesheets.get_subjects');
            Route::get('/{heeDatesheet}/edit', [HeDatesheetController::class, 'edit'])->name('he.datesheets.edit');
            Route::put('/{heeDatesheet}', [HeDatesheetController::class, 'update'])->name('he.datesheets.update');
            Route::delete('/{heeDatesheet}', [HeDatesheetController::class, 'destroy'])->name('he.datesheets.destroy');
        });

        // HE University Roll Numbers
        Route::get('/uni-roll-numbers', [HeRollNumberController::class, 'index'])->name('he.roll-numbers.index');
        Route::post('/uni-roll-numbers/update', [HeRollNumberController::class, 'update'])->name('he.roll-numbers.update');
        
        // HE Seating Plans
        Route::resource('seating-plans', HeSeatingPlanController::class)->names('he.seating_plans');
        Route::get('seating-plans/{seatingPlan}/attendance-sheet', [HeSeatingPlanController::class, 'attendanceSheet'])->name('he.seating_plans.attendance_sheet');

        // HE Timetables
        Route::resource('timetables', HeTimetableController::class)->names([
            'index' => 'he.timetables.index',
            'create' => 'he.timetables.create',
            'store' => 'he.timetables.store',
            'destroy' => 'he.timetables.destroy',
        ])->only(['index', 'create', 'store', 'destroy']);
        Route::post('timetables/clone', [HeTimetableController::class, 'clone'])->name('he.timetables.clone');
        Route::delete('timetables/destroy-all', [HeTimetableController::class, 'destroyAll'])->name('he.timetables.destroy_all');
        Route::get('timetables/get-semester-subjects', [HeTimetableController::class, 'getSemesterSubjects'])->name('he.timetables.get_semester_subjects');

        // HE Invigilator Routes
        Route::prefix('invigilators')->group(function () {
            Route::get('/', [HeInvigilatorController::class, 'index'])->name('he.invigilators.index');
            Route::get('/assign', [HeInvigilatorController::class, 'assignView'])->name('he.invigilators.assign');
            Route::get('/create', [HeInvigilatorController::class, 'create'])->name('he.invigilators.create');
            Route::post('/auto-assign', [HeInvigilatorController::class, 'autoAssign'])->name('he.invigilators.auto_assign');
            Route::get('/rooms-by-date', [HeInvigilatorController::class, 'getRoomsByDate'])->name('he.invigilators.rooms_by_date');
            Route::get('/duty-roster', [HeInvigilatorController::class, 'dutyRoster'])->name('he.invigilators.duty_roster');
            Route::post('/store-bulk', [HeInvigilatorController::class, 'storeBulk'])->name('he.invigilators.store_bulk');
        });

        // HE Admin Reports
        Route::prefix('reports')->group(function () {
            Route::get('/award-list', [HeReportController::class, 'awardListForm'])->name('he.reports.award_list.form');
            Route::post('/award-list', [HeReportController::class, 'generateAwardList'])->name('he.reports.award_list.generate');
            Route::get('/tabulation-sheet', [HeReportController::class, 'tabulationSheetForm'])->name('he.reports.tabulation_sheet.form');
            Route::post('/tabulation-sheet', [HeReportController::class, 'generateTabulationSheet'])->name('he.reports.tabulation_sheet.generate');
            Route::get('/transcript-search', [HeReportController::class, 'transcriptSearch'])->name('he.reports.transcript.search');
            Route::get('/transcript/{admission}', [HeReportController::class, 'generateTranscript'])->name('he.reports.transcript.generate');
            Route::get('/attendance', [HeReportController::class, 'attendanceReportForm'])->name('he.reports.attendance.form');
            Route::post('/attendance', [HeReportController::class, 'generateAttendanceReport'])->name('he.reports.attendance.generate');
        });
    });

    // Common/Teacher Functional Routes
    Route::get('/get-subjects', [HeResultController::class, 'getSubjects'])->name('he.get_subjects');

    // HE Results
    Route::prefix('results')->middleware('role:admin,principal,bs_clerk,teacher')->group(function () {
        Route::get('/', [HeResultController::class, 'index'])->name('he.results.index');
        Route::get('/create', [HeResultController::class, 'create'])->name('he.results.create');
        Route::post('/fetch-students', [HeResultController::class, 'fetchStudents'])->name('he.results.fetch_students');
        Route::post('/', [HeResultController::class, 'store'])->name('he.results.store');
        Route::post('/lock', [HeResultController::class, 'lockResults'])->name('he.results.lock');
        Route::get('/edit', [HeResultController::class, 'edit'])->name('he.results.edit');
        Route::post('/update', [HeResultController::class, 'update'])->name('he.results.update');
        Route::get('/get-subjects', [HeResultController::class, 'getSubjects'])->name('he.results.get_subjects');
        Route::get('/summary', [HeResultController::class, 'summary'])->name('he.results.summary');
    });

    // HE Attendance
    Route::prefix('attendance')->middleware('role:teacher,admin,principal,bs_clerk')->group(function () {
        Route::get('/', [HeAttendanceController::class, 'index'])->name('he.attendance.index');
        Route::get('/create', [HeAttendanceController::class, 'create'])->name('he.attendance.create');
        Route::post('/', [HeAttendanceController::class, 'store'])->name('he.attendance.store');
        Route::get('/report', [HeAttendanceController::class, 'report'])->name('he.attendance.report');
    });
});
