<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SectionController;

use App\Http\Controllers\LookupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdmissionApplicationController;
use App\Http\Controllers\AdmissionSettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TestAttendanceController;
use App\Http\Controllers\SubjectEnrollmentReportController;
use App\Http\Controllers\FinalResultReportController;
use App\Http\Controllers\TestResultReportController;
use App\Http\Controllers\DetailedTestResultReportController;
use App\Http\Controllers\DetailedFinalResultReportController;
use App\Http\Controllers\SubjectFinalResultReportController;
use App\Http\Controllers\TestAttendanceReportController;
use App\Http\Controllers\CashRegisterReportController;
use App\Http\Controllers\CustomStudentReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\BiseScraperController;
use App\Http\Controllers\DatesheetController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SeatingPlanController;
use App\Http\Controllers\AdminTimetableController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSectionController;
use App\Http\Controllers\GateAttendanceController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\InvigilatorController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\SubjectGroupController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\RoleSwitcherController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\FeeHeadController;
use App\Http\Controllers\SubjectAssignmentRuleController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\HostelController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\MyLeaveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\DatabaseAuditController;

/*
|--------------------------------------------------------------------------
| Web Routes Entry Point
|--------------------------------------------------------------------------
|
| Clean modularized route files loaded seamlessly.
|
*/

// 1. Unauthenticated Public Routes
require __DIR__.'/public.php';

// 2. Authentication & Password Reset Routes
require __DIR__.'/auth.php';

// 3. Protected Application Routes (Require Authentication)
Route::middleware(['auth'])->group(function () {

    // Main Dashboard & Global Utilities
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/clear-cache', [DashboardController::class, 'clearCache'])->name('clear-cache');
    Route::get('/duty-roster', [App\Http\Controllers\PublicController::class, 'dutyRosterLookup'])->name('public.duty_roster_lookup');

    // Role & View Switcher
    Route::post('/switch-role', [RoleSwitcherController::class, 'switch'])->name('switch.role');
    Route::post('/switch-view', [RoleSwitcherController::class, 'switchView'])->name('switch.view');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ID Card Routes
    Route::get('/students/id-cards', [IdCardController::class, 'index'])->name('students.id_cards.index');
    Route::get('/students/id-cards/generate', [IdCardController::class, 'generate'])->name('students.id_cards.generate');

    // Core Domain Route Modules
    require __DIR__.'/admin.php';
    require __DIR__.'/students.php';
    require __DIR__.'/hr.php';
    require __DIR__.'/higher_education.php';
    require __DIR__.'/lms.php';

    // Academic Lookups & Setup
    Route::middleware(['role:admin,principal,clerk,hssc_admin'])->group(function () {
        Route::get('bise', [LookupController::class, 'biseindex'])->name('bise.biseindex');
        Route::post('bise', [LookupController::class, 'bisestore'])->name('bise.bisestore');
        Route::put('bise/{bise}', [LookupController::class, 'biseupdate'])->name('bise.biseupdate');
        Route::delete('bise/{bise}', [LookupController::class, 'bisedestroy'])->name('bise.bisedestroy');
    });

    Route::prefix('blood-group')->group(function () {
        Route::get('/', [LookupController::class, 'bloodGroupIndex'])->name('bloodGroup.index');
        Route::post('/', [LookupController::class, 'bloodGroupStore'])->name('bloodGroup.store');
        Route::put('/{bloodGroup}', [LookupController::class, 'bloodGroupUpdate'])->name('bloodGroup.update');
        Route::delete('/{bloodGroup}', [LookupController::class, 'bloodGroupDestroy'])->name('bloodGroup.destroy');
    });

    Route::prefix('quota')->group(function () {
        Route::get('/', [LookupController::class, 'quotaIndex'])->name('quota.index');
        Route::post('/', [LookupController::class, 'quotaStore'])->name('quota.store');
        Route::put('/{quota}', [LookupController::class, 'quotaUpdate'])->name('quota.update');
        Route::delete('/{quota}', [LookupController::class, 'quotaDestroy'])->name('quota.destroy');
    });

    Route::prefix('designations')->middleware('role:admin,principal,super_admin,clerk,bs_clerk,hssc_clerk')->group(function () {
        Route::get('/', [DesignationController::class, 'index'])->name('designations.index');
        Route::post('/', [DesignationController::class, 'store'])->name('designations.store');
        Route::put('/{designation}', [DesignationController::class, 'update'])->name('designations.update');
        Route::delete('/{designation}', [DesignationController::class, 'destroy'])->name('designations.destroy');
    });

    Route::prefix('subject')->middleware('role:admin,principal,clerk,hssc_admin')->group(function () {
        Route::get('/', [LookupController::class, 'subjectIndex'])->name('subject.index');
        Route::get('/create', [LookupController::class, 'subjectCreate'])->name('subject.create');
        Route::post('/', [LookupController::class, 'subjectStore'])->name('subject.store');
        Route::get('/{subject}/edit', [LookupController::class, 'subjectEdit'])->name('subject.edit');
        Route::put('/{subject}', [LookupController::class, 'subjectUpdate'])->name('subject.update');
        Route::delete('/{subject}', [LookupController::class, 'subjectDestroy'])->name('subject.destroy');
        Route::get('/{subject}', [LookupController::class, 'subjectShow'])->name('subject.show');
    });

    Route::resource('subject-groups', SubjectGroupController::class)->middleware('role:admin,principal,clerk,hssc_admin');

    // Voucher Management
    Route::prefix('vouchers')->name('vouchers.')->middleware('role:admin,principal,clerk,hssc_clerk,hssc_admin,accountant,hssc_accountant,bs_accountant')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::get('/bulk/class-12', [VoucherController::class, 'bulkGenerateClass12Form'])->name('bulk-class12');
        Route::post('/bulk/class-12', [VoucherController::class, 'bulkGenerateClass12'])->name('bulk-class12.store');
        Route::get('/bulk/he', [VoucherController::class, 'bulkGenerateHEForm'])->name('bulk-he');
        Route::post('/bulk/he', [VoucherController::class, 'bulkGenerateHE'])->name('bulk-he.store');
        Route::get('/fine/{student}', [VoucherController::class, 'createFineForm'])->name('fine.create');
        Route::post('/fine/{student}', [VoucherController::class, 'storeFine'])->name('fine.store');
        Route::get('/{voucher}', [VoucherController::class, 'show'])->name('show');
        Route::post('/{voucher}/confirm', [VoucherController::class, 'confirmPayment'])->name('confirm');
        Route::post('/{voucher}/cancel', [VoucherController::class, 'cancelVoucher'])->name('cancel');
        Route::post('/bulk-download', [VoucherController::class, 'bulkDownload'])->name('bulk-download');
        Route::post('/bulk-delete', [VoucherController::class, 'bulkDelete'])->name('bulk-delete');
    });

    // Admission Applications
    Route::prefix('admission-applications')->middleware('role:admin,principal,clerk,hssc_clerk,bs_clerk,hssc_admin,bs_admin')->group(function () {
        Route::get('/', [AdmissionApplicationController::class, 'index'])->name('admission-applications.index');
        Route::get('/create', [AdmissionApplicationController::class, 'create'])->name('admission-applications.create');
        Route::post('/', [AdmissionApplicationController::class, 'store'])->name('admission-applications.store');
        Route::get('/merit-list', [AdmissionApplicationController::class, 'generateMeritList'])->name('admission-applications.merit-list');
        Route::post('/merit-list', [AdmissionApplicationController::class, 'processMeritList'])->name('admission-applications.process-merit-list');

        // Admission Settings
        Route::get('/settings', [AdmissionSettingsController::class, 'index'])->name('admissions.settings.index');
        Route::post('/settings', [AdmissionSettingsController::class, 'update'])->name('admissions.settings.update');
        Route::post('/settings/migrate', [AdmissionSettingsController::class, 'migrateExisting'])->name('admissions.settings.migrate');
        Route::get('/{application}', [AdmissionApplicationController::class, 'show'])->name('admission-applications.show');
        Route::get('/{application}/edit', [AdmissionApplicationController::class, 'edit'])->name('admission-applications.edit');
        Route::put('/{application}', [AdmissionApplicationController::class, 'update'])->name('admission-applications.update');
        Route::delete('/{application}', [AdmissionApplicationController::class, 'destroy'])->name('admission-applications.destroy');
        Route::post('/{application}/review', [AdmissionApplicationController::class, 'review'])->name('admission-applications.review');
        Route::post('/{application}/generate-voucher', [AdmissionApplicationController::class, 'generateVoucher'])->name('admission-applications.generate-voucher');
        Route::get('/{application}/voucher-preview', [AdmissionApplicationController::class, 'voucherPreview'])->name('admission-applications.voucher-preview');
        Route::post('/{application}/generate-voucher-custom', [AdmissionApplicationController::class, 'generateVoucherCustom'])->name('admission-applications.generate-voucher-custom');
        Route::post('/{application}/confirm-payment', [AdmissionApplicationController::class, 'confirmPayment'])->name('admission-applications.confirm-payment');
        Route::post('/{application}/admit-late', [AdmissionApplicationController::class, 'admitLate'])->name('admission-applications.admit-late');
    });

    // Staff Management
    Route::prefix('staff')->middleware('role:admin,principal,super_admin,clerk,hssc_clerk,hssc_admin')->group(function () {
        Route::get('/panel', [StaffController::class, 'staffPanel'])->name('staff.panel');
        Route::get('/student-assignment', [StaffController::class, 'studentAssignment'])->name('staff.student_assignment');
        Route::get('/get-sections-by-year', [StaffController::class, 'getSectionsByYear'])->name('staff.get_sections_by_year');
        Route::get('/get-subjects-by-year', [StaffController::class, 'getSubjectsByYear'])->name('staff.get_subjects_by_year');
        Route::get('/get-teachers-by-subject', [StaffController::class, 'getTeachersBySubject'])->name('staff.get_teachers_by_subject');
        Route::get('/unassigned-students', [StaffController::class, 'getUnassignedStudents'])->name('staff.unassigned_students');
        Route::get('/assigned-students', [StaffController::class, 'getAssignedStudents'])->name('staff.assigned_students');
        Route::post('/assign-students', [StaffController::class, 'assignStudents'])->name('staff.assign_students');
        Route::post('/unassign-students', [StaffController::class, 'unassignStudents'])->name('staff.unassign_students');
        Route::get('/award-list', [StaffController::class, 'awardListForm'])->name('staff.award_list.form');
        Route::post('/award-list/generate', [StaffController::class, 'generateAwardList'])->name('staff.award_list.generate');
        Route::get('/download-award-list', [StaffController::class, 'downloadAwardList'])->name('staff.download_award_list');
        Route::get('/export', [StaffController::class, 'export'])->name('staff.export');
        Route::get('/designation-bps', [StaffController::class, 'getDesignationBps'])->name('staff.designation_bps');
        Route::get('/', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/{staff}', [StaffController::class, 'show'])->name('staff.show');
        Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
    });

    // Student Affairs — Sections Management
    Route::prefix('student-affairs')->name('student-affairs.')->middleware('role:admin,principal,clerk,hssc_admin,hssc_clerk')->group(function () {
        Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::post('/sections/clear', [SectionController::class, 'clearSection'])->name('sections.clear');
        Route::post('/sections/copy', [SectionController::class, 'copySections'])->name('sections.copy');
        Route::get('/sections/get-subjects', [SectionController::class, 'getSubjects'])->name('sections.get-subjects');
    });

    // Test Attendance
    Route::prefix('test-attendance')->name('test-attendance.')->group(function () {
        Route::get('/create', [TestAttendanceController::class, 'create'])->name('create');
        Route::post('/fetch-students', [TestAttendanceController::class, 'fetchStudents'])->name('fetch_students');
        Route::post('/store', [TestAttendanceController::class, 'store'])->name('store');
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/subject-enrollment', [SubjectEnrollmentReportController::class, 'index'])->name('reports.subject-enrollment.index');
        Route::get('/subject-enrollment/get-subjects', [SubjectEnrollmentReportController::class, 'getSubjects'])->name('reports.subject-enrollment.get-subjects');
        Route::post('/subject-enrollment/show', [SubjectEnrollmentReportController::class, 'showReport'])->name('reports.subject-enrollment.show');
        Route::post('/subject-enrollment/pdf', [SubjectEnrollmentReportController::class, 'exportPdf'])->name('reports.subject-enrollment.pdf');
    
        Route::get('/students-without-teacher', [SubjectEnrollmentReportController::class, 'withoutTeacherIndex'])->name('reports.students-without-teacher.index');
        Route::post('/students-without-teacher/show', [SubjectEnrollmentReportController::class, 'withoutTeacherReport'])->name('reports.students-without-teacher.show');
        Route::post('/students-without-teacher/pdf', [SubjectEnrollmentReportController::class, 'withoutTeacherPdf'])->name('reports.students-without-teacher.pdf');

        Route::any('/final-result-report', [FinalResultReportController::class, 'index'])->name('reports.final-result-report.index');
        Route::get('/test-result-report', [TestResultReportController::class, 'index'])->name('reports.test-result-report.index');
        Route::get('/detailed-test-result-report', [DetailedTestResultReportController::class, 'index'])->name('reports.detailed-test-result-report.index');
        Route::get('/detailed-final-result-report', [DetailedFinalResultReportController::class, 'index'])->name('reports.detailed-final-result-report.index');
        Route::get('/subject-final-result-report', [SubjectFinalResultReportController::class, 'index'])->name('reports.subject-final-result-report.index');
        Route::get('/subject-final-result-report/get-subjects', [SubjectFinalResultReportController::class, 'getSubjects'])->name('reports.subject-final-result-report.get-subjects');
        Route::get('/test-attendance-report', [TestAttendanceReportController::class, 'index'])->name('reports.test-attendance-report');

        Route::get('/cash-register', [CashRegisterReportController::class, 'index'])->name('reports.cash-register.index');
        Route::post('/cash-register', [CashRegisterReportController::class, 'show'])->name('reports.cash-register.show');
        Route::get('/custom-student-report', [CustomStudentReportController::class, 'index'])->name('reports.custom-student-report');
        Route::get('/custom-student-report/export', [CustomStudentReportController::class, 'export'])->name('reports.custom-student-report.export');
    });

    // Results Management
    Route::prefix('results')->group(function () {
        Route::get('/', [ResultController::class, 'index'])->name('results.index');
        Route::get('/create', [ResultController::class, 'create'])->name('results.create');
        Route::post('/fetch-students', [ResultController::class, 'fetchStudents'])->name('results.fetch_students');
        Route::post('/get-test-details', [ResultController::class, 'getTestDetails'])->name('results.get_test_details');
        Route::post('/', [ResultController::class, 'store'])->name('results.store');
        Route::get('/edit', [ResultController::class, 'edit'])->name('results.edit');
        Route::post('/update', [ResultController::class, 'update'])->name('results.update');
        Route::delete('/destroy', [ResultController::class, 'destroy'])->name('results.destroy');
        Route::get('/student/{student}', [ResultController::class, 'studentResults'])->name('results.student');
        
        Route::get('/final/create', [ResultController::class, 'createFinalResult'])->name('results.final.create');
        Route::post('/final/search', [ResultController::class, 'searchFinalStudent'])->name('results.final.search');
        Route::post('/final/store', [ResultController::class, 'storeFinalResult'])->name('results.final.store');
        Route::get('/final/view', [ResultController::class, 'viewFinalResults'])->name('results.final.view');

        Route::get('/11th/create', [ResultController::class, 'create11thResult'])->name('results.11th.create');
        Route::post('/11th/search', [ResultController::class, 'search11thStudent'])->name('results.11th.search');
        Route::post('/11th/store', [ResultController::class, 'store11thResult'])->name('results.11th.store');
        
        Route::post('/bise/fetch', [BiseScraperController::class, 'fetchResult'])->name('results.bise.fetch');
    });

    // Datesheets & Seating Plans
    Route::middleware(['role:admin,principal,clerk,hssc_clerk,hssc_admin'])->group(function () {
        Route::get('datesheets/summary', [DatesheetController::class, 'summary'])->name('datesheets.summary');
        Route::get('datesheets/generate', [DatesheetController::class, 'generate'])->name('datesheets.generate');
        Route::post('datesheets/generate', [DatesheetController::class, 'storeGenerated'])->name('datesheets.store_generated');
        Route::post('datesheets/bulk-delete', [DatesheetController::class, 'bulkDestroy'])->name('datesheets.bulk_destroy');
        Route::resource('datesheets', DatesheetController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::resource('rooms', RoomController::class);
        Route::get('seating-plans/{seatingPlan}/attendance', [SeatingPlanController::class, 'attendanceSheet'])->name('seating-plans.attendance');
        Route::post('seating-plans/bulk-delete', [SeatingPlanController::class, 'bulkDestroy'])->name('seating-plans.bulk_destroy');
        Route::resource('seating-plans', SeatingPlanController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    });

    // Timetables
    Route::prefix('admin/timetables')->middleware('role:admin,principal,clerk')->group(function () {
        Route::get('/', [AdminTimetableController::class, 'index'])->name('admin.timetables.index');
        Route::get('/teacher-batch', [AdminTimetableController::class, 'teacherBatchIndex'])->name('admin.timetables.teacher_batch');
        Route::post('/teacher-batch', [AdminTimetableController::class, 'storeTeacherBatch'])->name('admin.timetables.store_teacher_batch');
        Route::get('/subject-batch', [AdminTimetableController::class, 'subjectBatchIndex'])->name('admin.timetables.subject_batch');
        Route::post('/subject-batch', [AdminTimetableController::class, 'storeSubjectBatch'])->name('admin.timetables.store_subject_batch');
        Route::post('/', [AdminTimetableController::class, 'store'])->name('admin.timetables.store');
        Route::post('/combined', [AdminTimetableController::class, 'storeCombined'])->name('admin.timetables.store_combined');
        Route::get('/check-availability', [AdminTimetableController::class, 'checkSlotAvailability'])->name('admin.timetables.check_availability');
        Route::post('/clone', [AdminTimetableController::class, 'cloneYear'])->name('admin.timetables.clone');
        Route::post('/toggle-status', [AdminTimetableController::class, 'toggleStatus'])->name('admin.timetables.toggle_status');
        Route::put('/{entry}', [AdminTimetableController::class, 'update'])->name('admin.timetables.update');
        Route::delete('/{entry}', [AdminTimetableController::class, 'destroy'])->name('admin.timetables.destroy');
        Route::delete('/bulk-delete/{entry}', [AdminTimetableController::class, 'bulkDestroy'])->name('admin.timetables.bulk_destroy');
        Route::get('/summary', [AdminTimetableController::class, 'summary'])->name('admin.timetables.summary');
        Route::resource('time-slots', TimeSlotController::class)->except(['show', 'create']);
    });

    // Database Audit & Orphan Cleanup
    Route::prefix('admin/database/audit')->middleware('role:admin,principal,super_admin')->group(function () {
        Route::get('/', [DatabaseAuditController::class, 'index'])->name('admin.database.audit.index');
        Route::post('/clean', [DatabaseAuditController::class, 'cleanCategory'])->name('admin.database.audit.clean');
        Route::post('/clean-all', [DatabaseAuditController::class, 'cleanAll'])->name('admin.database.audit.clean_all');
    });

    // Attendance
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/create', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('/', [AttendanceController::class, 'store'])->name('attendance.store');
        
        Route::get('/sections', [AttendanceSectionController::class, 'index'])->name('attendance.sections.index');
        Route::post('/sections', [AttendanceSectionController::class, 'store'])->name('attendance.sections.store');

        Route::prefix('gate')->name('attendance.gate.')->group(function () {
            Route::get('/', [GateAttendanceController::class, 'index'])->name('index');
            Route::post('/scan', [GateAttendanceController::class, 'scan'])->name('scan');
        });

        Route::middleware(['role:admin,principal,clerk,hssc_clerk,bs_clerk,hssc_admin,bs_admin,accountant,hssc_accountant,bs_accountant,teacher'])->group(function () {
            Route::get('/reports', [AttendanceReportController::class, 'index'])->name('attendance.reports.form');
            Route::get('/reports/generate', [AttendanceReportController::class, 'generate'])->name('attendance.report.generate');
            Route::post('/reports', [AttendanceReportController::class, 'generate'])->name('attendance.reports.generate');
        });

        Route::middleware(['role:admin,super_admin,principal,hssc_admin'])->group(function () {
            Route::get('/reports/teacher-status', [AttendanceReportController::class, 'teacherStatus'])->name('reports.teacher_attendance_status');
            Route::post('/reports/teacher-status/send-reminders', [AttendanceReportController::class, 'sendReminders'])->name('attendance.reports.send_reminders');
            Route::get('/reports/teacher-workload', [AttendanceReportController::class, 'teacherWorkload'])->name('reports.teacher_workload');
            Route::get('/reports/whole-year', [AttendanceReportController::class, 'wholeYearForm'])->name('attendance.reports.whole_year.form');
            Route::post('/reports/whole-year', [AttendanceReportController::class, 'generateWholeYearReport'])->name('attendance.reports.whole_year.generate');
        });
    });

    // Invigilators
    Route::prefix('invigilators')->middleware('role:admin,principal,clerk,hssc_clerk,hssc_admin')->group(function () {
        Route::get('/', [InvigilatorController::class, 'index'])->name('invigilators.index');
        Route::get('/assign', [InvigilatorController::class, 'assignView'])->name('invigilators.assign');
        Route::get('/create', [InvigilatorController::class, 'create'])->name('invigilators.create');
        Route::post('/auto-assign', [InvigilatorController::class, 'autoAssign'])->name('invigilators.auto_assign');
        Route::get('/rooms-by-date', [InvigilatorController::class, 'getRoomsByDate'])->name('invigilators.rooms_by_date');
        Route::get('/duty-roster', [InvigilatorController::class, 'dutyRoster'])->name('invigilators.duty_roster');
        Route::post('/store', [InvigilatorController::class, 'store'])->name('invigilators.store');
        Route::post('/store-bulk', [InvigilatorController::class, 'storeBulk'])->name('invigilators.store_bulk');
        Route::delete('/delete-by-date', [InvigilatorController::class, 'deleteByDate'])->name('invigilators.delete_by_date');
        Route::put('/{assignment}', [InvigilatorController::class, 'update'])->name('invigilators.update');
        Route::delete('/{assignment}', [InvigilatorController::class, 'destroy'])->name('invigilators.destroy');
        Route::post('/bulk-delete', [InvigilatorController::class, 'bulkDestroy'])->name('invigilators.bulk_destroy');
        
        Route::get('/duty-rules', [InvigilatorController::class, 'dutyRules'])->name('invigilators.duty_rules');
        Route::post('/duty-rules', [InvigilatorController::class, 'storeDutyRule'])->name('invigilators.store_duty_rule');
        
        Route::get('/exemptions', [InvigilatorController::class, 'exemptions'])->name('invigilators.exemptions');
        Route::post('/exemptions', [InvigilatorController::class, 'storeExemption'])->name('invigilators.store_exemption');
        Route::delete('/exemptions/{exemption}', [InvigilatorController::class, 'destroyExemption'])->name('invigilators.destroy_exemption');
    });

    // Advanced Admissions Rules & Fee Heads
    Route::prefix('settings/admissions')->middleware('role:admin,principal,super_admin')->group(function () {
        Route::resource('fee-heads', FeeHeadController::class)->except(['show']);
        Route::resource('subject-rules', SubjectAssignmentRuleController::class)->except(['show']);
    });

    // Notification Settings & Templates
    Route::prefix('settings/notifications')->middleware('role:admin,super_admin')->group(function () {
        Route::get('/', [NotificationSettingsController::class, 'index'])->name('settings.notifications.index');
        Route::post('/update', [NotificationSettingsController::class, 'update'])->name('settings.notifications.update');
    });

    Route::prefix('communication')->middleware('role:admin,super_admin')->group(function () {
        Route::resource('notification-templates', NotificationTemplateController::class)->except(['show']);
        Route::resource('announcements', AnnouncementController::class)->only(['index', 'create', 'store', 'destroy']);
    });

    // Online Payments
    Route::get('pay/{voucher_number}', [OnlinePaymentController::class, 'checkout'])->name('payment.checkout');
    Route::post('pay/{id}/initiate', [OnlinePaymentController::class, 'initiate'])->name('payment.initiate');
    Route::get('payment/processing/{transaction_id}', [OnlinePaymentController::class, 'processing'])->name('payment.processing');
    Route::post('payment/callback', [OnlinePaymentController::class, 'callback'])->name('payment.callback');

    // Bulk Data Imports
    Route::get('imports', [DataImportController::class, 'index'])->name('imports.index');
    Route::post('imports/staff', [DataImportController::class, 'importStaff'])->name('imports.staff');
    Route::post('imports/students', [DataImportController::class, 'importStudents'])->name('imports.students');

    // Parent Portal
    Route::prefix('parent')->group(function () {
        Route::get('dashboard', [ParentPortalController::class, 'index'])->name('parent.dashboard');
        Route::get('child/{id}', [ParentPortalController::class, 'childDetails'])->name('parent.child');
    });

    // Transport Management
    Route::prefix('admin/transport')->group(function () {
        Route::get('/', [TransportController::class, 'index'])->name('transport.index');
        Route::get('/create', [TransportController::class, 'create'])->name('transport.create');
        Route::post('/', [TransportController::class, 'store'])->name('transport.store');
    });

    // Hostel Management
    Route::prefix('admin/hostel')->group(function () {
        Route::get('/', [HostelController::class, 'index'])->name('hostel.index');
        Route::get('/{id}/rooms', [HostelController::class, 'rooms'])->name('hostel.rooms');
        Route::post('/{id}/rooms', [HostelController::class, 'storeRoom'])->name('hostel.rooms.store');
    });

    // Alumni & Graduation
    Route::prefix('admin/alumni')->group(function () {
        Route::get('/', [AlumniController::class, 'index'])->name('alumni.index');
        Route::get('/batches', [AlumniController::class, 'batches'])->name('alumni.batches');
    });

    // Asset & Budget
    Route::prefix('admin/assets')->group(function () {
        Route::get('/', [AssetController::class, 'index'])->name('assets.index');
        Route::post('/', [AssetController::class, 'store'])->name('assets.store');
        Route::put('/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
        Route::get('/categories', [AssetController::class, 'categories'])->name('assets.categories');
        Route::post('/categories', [AssetController::class, 'storeCategory'])->name('assets.store_category');
    });

    Route::prefix('admin/budgets')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('budgets.index');
        Route::post('/', [BudgetController::class, 'store'])->name('budgets.store');
        Route::get('/{id}/expenditures', [BudgetController::class, 'expenditures'])->name('budgets.expenditures');
        Route::post('/{id}/expenditures', [BudgetController::class, 'storeExpenditure'])->name('budgets.store_expenditure');
    });

    // Staff Portal / Personal Leaves
    Route::prefix('my-leaves')->group(function () {
        Route::get('/', [MyLeaveController::class, 'index'])->name('my-leaves.index');
        Route::get('/create', [MyLeaveController::class, 'create'])->name('my-leaves.create');
        Route::post('/', [MyLeaveController::class, 'store'])->name('my-leaves.store');
    });

    // Leave Types Management (Admin / Principal / Super Admin)
    Route::prefix('leave-types')->middleware('role:admin,principal,super_admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\LeaveTypeController::class, 'index'])->name('leave-types.index');
        Route::post('/', [\App\Http\Controllers\LeaveTypeController::class, 'store'])->name('leave-types.store');
        Route::put('/{leaveType}', [\App\Http\Controllers\LeaveTypeController::class, 'update'])->name('leave-types.update');
        Route::delete('/{leaveType}', [\App\Http\Controllers\LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');
    });

    // In-App Notifications
    Route::prefix('notifications')->middleware('role:principal,super_admin')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::delete('/', [NotificationController::class, 'destroyAll'])->name('notifications.destroyAll');
    });

    // Library Management System
    Route::prefix('library')->name('library.')->middleware('role:admin,principal,clerk')->group(function () {
        Route::get('/', [LibraryController::class, 'index'])->name('index');

        // Books
        Route::get('/books', [LibraryController::class, 'books'])->name('books');
        Route::get('/books/create', [LibraryController::class, 'createBook'])->name('books.create');
        Route::post('/books', [LibraryController::class, 'storeBook'])->name('books.store');
        Route::get('/books/{book}/edit', [LibraryController::class, 'editBook'])->name('books.edit');
        Route::put('/books/{book}', [LibraryController::class, 'updateBook'])->name('books.update');
        Route::delete('/books/{book}', [LibraryController::class, 'destroyBook'])->name('books.destroy');

        // Issue / Return
        Route::get('/issued', [LibraryController::class, 'issuedBooks'])->name('issued_books');
        Route::get('/issue', [LibraryController::class, 'issueBook'])->name('issue_book');
        Route::post('/issue', [LibraryController::class, 'storeIssue'])->name('store_issue');
        Route::post('/return/{issue}', [LibraryController::class, 'returnBook'])->name('return_book');
        Route::get('/search-borrower', [LibraryController::class, 'searchBorrower'])->name('search_borrower');

        // Authors
        Route::get('/authors', [LibraryController::class, 'authors'])->name('authors');
        Route::post('/authors', [LibraryController::class, 'storeAuthor'])->name('authors.store');
        Route::delete('/authors/{author}', [LibraryController::class, 'destroyAuthor'])->name('authors.destroy');

        // Genres
        Route::get('/genres', [LibraryController::class, 'genres'])->name('genres');
        Route::post('/genres', [LibraryController::class, 'storeGenre'])->name('genres.store');
        Route::delete('/genres/{genre}', [LibraryController::class, 'destroyGenre'])->name('genres.destroy');

        // Locations
        Route::get('/locations', [LibraryController::class, 'locations'])->name('locations');
        Route::post('/locations', [LibraryController::class, 'storeLocation'])->name('locations.store');
        Route::delete('/locations/{location}', [LibraryController::class, 'destroyLocation'])->name('locations.destroy');

        // Import / Export
        Route::get('/import-export', [LibraryController::class, 'importExport'])->name('import_export');
        Route::post('/import', [LibraryController::class, 'importBooks'])->name('import_books');
        Route::get('/export', [LibraryController::class, 'exportBooks'])->name('export_books');
    });


    // Struck-Off Management
    Route::prefix('struck-off')->name('struck-off.')->group(function () {
        // Teacher, Admin, Principal, Clerk: view eligible students, list struck-off students, history, and readmit (policy enforced by FormRequest/Service)
        Route::middleware('role:teacher,admin,principal,super_admin,clerk,hssc_clerk,hssc_admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\StruckOffController::class, 'index'])->name('index');
            Route::get('/teacher', [\App\Http\Controllers\StruckOffController::class, 'teacherPanel'])->name('teacher');
            Route::post('/', [\App\Http\Controllers\StruckOffController::class, 'store'])->name('store');
            Route::get('/student/{student}/history', [\App\Http\Controllers\StruckOffController::class, 'history'])->name('history');
            Route::post('/{log}/readmit', [\App\Http\Controllers\StruckOffController::class, 'readmit'])->name('readmit');
        });

        // Admin / Principal / Clerk: settings, bulk readmit
        Route::middleware('role:admin,principal,clerk,hssc_clerk,hssc_admin,super_admin')->group(function () {
            Route::post('/settings', [\App\Http\Controllers\StruckOffController::class, 'updateSettings'])->name('settings.update');
            Route::post('/bulk-readmit', [\App\Http\Controllers\StruckOffController::class, 'bulkReadmit'])->name('bulk-readmit');
        });
    });

    // Student Fine Management (Accessible only by admin, principal, clerk and accounts)
    Route::prefix('fines')->name('fines.')->middleware('role:admin,principal,clerk,hssc_clerk,hssc_admin,super_admin,bs_admin,bs_clerk,accountant,hssc_accountant,bs_accountant')->group(function () {
        // View fines and printable slips
        Route::get('/', [\App\Http\Controllers\FineController::class, 'index'])->name('index');
        Route::get('/slip/{fine}', [\App\Http\Controllers\FineController::class, 'printSlip'])->name('slip');
        Route::get('/summary-print', [\App\Http\Controllers\FineController::class, 'printSummary'])->name('print-summary');

        // Administrative & Accounts: calculation, generation, policy rules, payments, and waivers
        Route::get('/generate', [\App\Http\Controllers\FineController::class, 'generateForm'])->name('generate');
        Route::post('/generate', [\App\Http\Controllers\FineController::class, 'storeGenerated'])->name('generate.store');
        Route::get('/rules', [\App\Http\Controllers\FineController::class, 'rulesIndex'])->name('rules');
        Route::post('/rules', [\App\Http\Controllers\FineController::class, 'storeRule'])->name('rules.store');
        Route::put('/rules/{rule}', [\App\Http\Controllers\FineController::class, 'updateRule'])->name('rules.update');
        Route::post('/{fine}/payment', [\App\Http\Controllers\FineController::class, 'collectPayment'])->name('collect-payment');
        Route::post('/{fine}/waive', [\App\Http\Controllers\FineController::class, 'waiveFine'])->name('waive');
    });

}); // End of auth middleware group

