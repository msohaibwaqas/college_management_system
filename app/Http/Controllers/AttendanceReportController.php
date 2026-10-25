<?php

namespace App\Http\Controllers;

use App\Models\Admissions;
use App\Models\Subject;
use App\Services\AttendanceReportService;
use App\Services\AcademicCalendarService;
use App\Services\TeacherWorkloadService;
use Illuminate\Http\Request;

/**
 * Controller orchestrating HSSC attendance reports and daily teacher compliance.
 * Delegates data aggregation and calculations to AttendanceReportService.
 */
class AttendanceReportController extends Controller
{
    public function __construct(
        protected AttendanceReportService $reportService,
        protected AcademicCalendarService $academicCalendarService,
        protected TeacherWorkloadService $teacherWorkloadService
    ) {}

    /**
     * Show attendance report form for HSSC.
     */
    public function index()
    {
        $user = auth()->user();
        $isTeacher = $user->isTeacher();
        $staffId = $isTeacher ? ($user->staff->id ?? null) : null;

        if ($isTeacher) {
            $admissionYears = Admissions::whereHas('student.studentTeacherSubjects', function($q) use ($staffId) {
                $q->where('staff_id', $staffId);
            })
            ->select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

            $subjects = Subject::whereIn('id', function($query) use ($staffId) {
                $query->select('subject_id')
                    ->from('student_teacher_subjects')
                    ->where('staff_id', $staffId);
            })->orderBy('name')->get();
        } else {
            $admissionYears = Admissions::distinctAdmissionYears();
            $subjects = Subject::orderBy('name')->get();
        }

        return view('attendance.reports.form', compact('admissionYears', 'subjects'));
    }

    /**
     * Generate attendance report for HSSC.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
            'current_class' => 'required|in:11,12',
            'subject_id' => 'required|exists:subjects,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $user = auth()->user();
        $isTeacher = $user->isTeacher();
        $staffId = $isTeacher ? ($user->staff->id ?? null) : null;
        $subject = Subject::findOrFail($request->subject_id);

        $data = $this->reportService->generateSubjectReport(
            (int) $request->admission_year,
            $request->current_class,
            $subject,
            $request->start_date,
            $request->end_date,
            $staffId
        );

        return view('attendance.reports.report', $data);
    }

    /**
     * Show form for Admin Whole Year Multi-Subject Attendance Report.
     */
    public function wholeYearForm()
    {
        $user = auth()->user();
        if (!$user->isAdministrativeStaff()) {
            abort(403, 'Unauthorized access to admin attendance reports.');
        }

        $admissionYears = Admissions::distinctAdmissionYears();
        return view('attendance.reports.whole_year_form', compact('admissionYears'));
    }

    /**
     * Generate Admin Whole Year Multi-Subject Attendance Report Matrix.
     */
    public function generateWholeYearReport(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdministrativeStaff()) {
            abort(403, 'Unauthorized access to admin attendance reports.');
        }

        $request->validate([
            'admission_year' => 'required|integer',
            'current_class' => 'required|in:11,12',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $data = $this->reportService->generateWholeYearMatrix(
            (int) $request->admission_year,
            $request->current_class,
            $request->start_date,
            $request->end_date
        );

        return view('attendance.reports.whole_year_matrix', $data);
    }

    /**
     * Daily Teacher Attendance Marking Status for Principal / Admin.
     */
    public function teacherStatus(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdministrativeStaff()) {
            abort(403, 'Unauthorized access to teacher attendance status.');
        }

        $date = $request->query('date', now()->toDateString());
        $statusFilter = $request->query('status_filter', 'all');
        $classFilter = $request->query('class_filter', '');

        $data = $this->reportService->buildTeacherStatus($date, $statusFilter, $classFilter);

        return view('attendance.reports.teacher_status', $data);
    }

    /**
     * Check if a given date is a Sunday or an announced holiday / public holiday.
     */
    public function checkIfSundayOrHoliday(string $dateString): array
    {
        return $this->academicCalendarService->checkIfSundayOrHoliday($dateString);
    }

    /**
     * Send bulk attendance reminders to teachers for pending attendance marking.
     */
    public function sendReminders(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdministrativeStaff()) {
            abort(403, 'Unauthorized access to send reminders.');
        }

        $request->validate([
            'reminders' => 'required|array|min:1',
            'reminders.*.staff_id' => 'required|integer',
            'reminders.*.subject_id' => 'required|integer',
            'reminders.*.class_level' => 'required|string',
            'reminders.*.admission_year' => 'required|integer',
            'reminders.*.attendance_date' => 'required|date',
            'custom_message' => 'nullable|string|max:500',
        ]);

        $res = $this->reportService->sendReminders(
            $request->reminders,
            $request->input('custom_message'),
            $user->id
        );

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', "Sent {$res['count']} attendance reminder notification(s) to teachers.");
    }

    /**
     * Teacher workload summary report.
     */
    public function teacherWorkload(Request $request)
    {
        $workload = $this->teacherWorkloadService->getTeacherWorkload();
        return view('attendance.reports.teacher_workload', compact('workload'));
    }
}
