<?php

namespace App\Http\Controllers;

use App\Models\Admissions;
use App\Models\Attendance;
use App\Models\AttendanceReminder;
use App\Models\Subject;
use App\Models\InstituteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Services\AcademicCalendarService;
use App\Services\TeacherWorkloadService;

class AttendanceReportController extends Controller
{
    public function __construct(
        protected AcademicCalendarService $academicCalendarService,
        protected TeacherWorkloadService $teacherWorkloadService
    ) {}

    /**
     * Show attendance report form for HSSC
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
     * Generate attendance report for HSSC
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

        $year = $request->admission_year;
        $class = $request->current_class;
        $subject = Subject::find($request->subject_id);
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $user = auth()->user();
        $isTeacher = $user->isTeacher();
        $staffId = $isTeacher ? ($user->staff->id ?? null) : null;

        // Get students enrolled in this subject and class
        // We use student_teacher_subjects to find students assigned to this subject
        $students = Admissions::with(['student.father', 'student.contact'])
            ->where('admission_year', $year)
            ->where('current_class', $class)
            ->whereIn('status', ['Active', 'Admitted', 'active', 'admitted'])
            ->whereHas('student.studentTeacherSubjects', function($q) use ($subject, $staffId) {
                $q->where('subject_id', $subject->id);
                if ($staffId) {
                    $q->where('staff_id', $staffId);
                }
            })
            ->get()
            ->sortBy('college_roll_no');

        // Fetch attendance records for these students in the date range
        $attendanceData = Attendance::whereIn('student_id', $students->pluck('student_id'))
            ->where('subject_id', $subject->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        $settings = InstituteSetting::getSettings();

        return view('attendance.reports.report', compact(
            'students', 
            'attendanceData', 
            'subject', 
            'class', 
            'year', 
            'startDate', 
            'endDate', 
            'settings'
        ));
    }

    /**
     * Show form for Admin Whole Year Multi-Subject Attendance Report
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
     * Generate Admin Whole Year Multi-Subject Attendance Report Matrix
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

        $year = $request->admission_year;
        $class = $request->current_class;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Get active/admitted students for class & admission year
        $students = Admissions::with(['student.father', 'student.contact'])
            ->where('admission_year', $year)
            ->where('current_class', $class)
            ->whereIn('status', ['Active', 'Admitted', 'active', 'admitted'])
            ->get()
            ->sortBy('college_roll_no');

        $studentIds = $students->pluck('student_id');

        // Get unique subject IDs in which these students are enrolled or have attendance records
        $enrolledSubjectIds = DB::table('student_teacher_subjects')
            ->whereIn('student_id', $studentIds)
            ->pluck('subject_id')
            ->merge(
                DB::table('attendances')
                    ->whereIn('student_id', $studentIds)
                    ->pluck('subject_id')
            )
            ->merge(
                DB::table('student_compulsory_subjects')
                    ->whereIn('student_id', $studentIds)
                    ->pluck('subject_id')
            )
            ->merge(
                DB::table('student_elective_subjects')
                    ->whereIn('student_id', $studentIds)
                    ->pluck('subject_id')
            )
            ->unique()
            ->filter()
            ->values();

        // Get subject models only for enrolled subjects
        $subjects = Subject::whereIn('id', $enrolledSubjectIds)
            ->orderBy('name')
            ->get();

        $subjectIds = $subjects->pluck('id');

        // Calculate total unique lectures delivered per subject for THESE students in the selected date range
        $deliveredPerSubject = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->select('subject_id', DB::raw('COUNT(DISTINCT attendance_date) as total_delivered'))
            ->groupBy('subject_id')
            ->pluck('total_delivered', 'subject_id');

        // Fetch attended counts grouped by student and subject via fast DB query in selected date range
        $attendedCounts = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->whereIn('status', ['present', 'late'])
            ->select('student_id', 'subject_id', DB::raw('COUNT(*) as attended_count'))
            ->groupBy('student_id', 'subject_id')
            ->get()
            ->keyBy(function($item) {
                return $item->student_id . '_' . $item->subject_id;
            });

        // Fast O(1) Matrix Construction
        $matrix = [];
        foreach ($students as $admission) {
            $sId = $admission->student_id;
            $matrix[$sId] = [];

            foreach ($subjects as $sub) {
                $delivered = $deliveredPerSubject->get($sub->id, 0);
                $key = $sId . '_' . $sub->id;
                $attended = isset($attendedCounts[$key]) ? $attendedCounts[$key]->attended_count : 0;

                $matrix[$sId][$sub->id] = [
                    'attended' => $attended,
                    'delivered' => $delivered
                ];
            }
        }

        $settings = InstituteSetting::getSettings();

        return view('attendance.reports.whole_year_report', compact(
            'students',
            'subjects',
            'deliveredPerSubject',
            'matrix',
            'class',
            'year',
            'startDate',
            'endDate',
            'settings'
        ));
    }

    /**
     * Daily Teacher Attendance Marking Status for Principal / Admin
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

        $dayOfWeek = \Carbon\Carbon::parse($date)->format('l');

        // Fetch active timetable entries scheduled on $dayOfWeek
        $ttToday = DB::table('timetable_entries')
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', 1)
            ->select('staff_id', 'subject_id', 'admission_year', 'section')
            ->get();

        $scheduledTodaySecKeys = [];
        $scheduledTodayGenKeys = [];

        foreach ($ttToday as $entry) {
            $genKey = $entry->staff_id . '_' . $entry->subject_id . '_' . $entry->admission_year;
            $scheduledTodayGenKeys[$genKey] = true;

            if (!empty($entry->section)) {
                $secKey = $genKey . '_' . trim($entry->section);
                $scheduledTodaySecKeys[$secKey] = true;
            }
        }

        // Fetch all active timetable entries across any day to check if section has timetable config
        $ttAll = DB::table('timetable_entries')
            ->where('is_active', 1)
            ->select('staff_id', 'subject_id', 'admission_year', 'section')
            ->get();

        $hasTimetableForSecKeys = [];
        foreach ($ttAll as $entry) {
            if (!empty($entry->section)) {
                $secKey = $entry->staff_id . '_' . $entry->subject_id . '_' . $entry->admission_year . '_' . trim($entry->section);
                $hasTimetableForSecKeys[$secKey] = true;
            }
        }

        // Fetch all assigned teaching blocks from student_teacher_subjects
        $blocksQuery = DB::table('student_teacher_subjects')
            ->join('staff', 'student_teacher_subjects.staff_id', '=', 'staff.id')
            ->join('subjects', 'student_teacher_subjects.subject_id', '=', 'subjects.id')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->leftJoin('student_subject_sections', function($join) {
                $join->on('student_teacher_subjects.student_id', '=', 'student_subject_sections.student_id')
                     ->on('student_teacher_subjects.subject_id', '=', 'student_subject_sections.subject_id')
                     ->on('admissions.admission_year', '=', 'student_subject_sections.admission_year')
                     ->whereNull('student_subject_sections.deleted_at');
            })
            ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('staff.deleted_at')
            ->whereNull('students.deleted_at')
            ->whereNull('admissions.deleted_at');

        if (!empty($classFilter)) {
            $blocksQuery->where('admissions.current_class', $classFilter);
        }

        $teachingBlocks = $blocksQuery->select(
                'staff.id as staff_id',
                'staff.first_name as staff_first_name',
                'staff.last_name as staff_last_name',
                'staff.staff_id as employee_id',
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'admissions.current_class as class_level',
                'admissions.admission_year',
                'student_subject_sections.section as attendance_section',
                DB::raw('COUNT(DISTINCT student_teacher_subjects.student_id) as assigned_student_count')
            )
            ->groupBy(
                'staff.id',
                'staff.first_name',
                'staff.last_name',
                'staff.staff_id',
                'subjects.id',
                'subjects.name',
                'admissions.current_class',
                'admissions.admission_year',
                'student_subject_sections.section'
            )
            ->orderBy('staff.first_name')
            ->orderBy('admissions.current_class')
            ->orderBy('subjects.name')
            ->get();

        // Check attendance records for the selected date
        $markedAttendance = DB::table('attendances')
            ->join('admissions', 'attendances.student_id', '=', 'admissions.student_id')
            ->leftJoin('student_teacher_subjects', function($join) {
                $join->on('attendances.student_id', '=', 'student_teacher_subjects.student_id')
                     ->on('attendances.subject_id', '=', 'student_teacher_subjects.subject_id')
                     ->on('attendances.staff_id', '=', 'student_teacher_subjects.staff_id')
                     ->whereNull('student_teacher_subjects.deleted_at');
            })
            ->leftJoin('student_subject_sections', function($join) {
                $join->on('attendances.student_id', '=', 'student_subject_sections.student_id')
                     ->on('attendances.subject_id', '=', 'student_subject_sections.subject_id')
                     ->on('admissions.admission_year', '=', 'student_subject_sections.admission_year')
                     ->whereNull('student_subject_sections.deleted_at');
            })
            ->whereDate('attendances.attendance_date', $date)
            ->whereNull('admissions.deleted_at')
            ->select(
                'attendances.staff_id',
                'attendances.subject_id',
                'admissions.current_class',
                'admissions.admission_year',
                DB::raw("COALESCE(student_subject_sections.section, '') as section"),
                DB::raw('COUNT(DISTINCT attendances.student_id) as marked_count'),
                DB::raw('MAX(attendances.updated_at) as last_marked_at')
            )
            ->groupBy(
                'attendances.staff_id',
                'attendances.subject_id',
                'admissions.current_class',
                'admissions.admission_year',
                DB::raw("COALESCE(student_subject_sections.section, '')")
            )
            ->get()
            ->keyBy(function($item) {
                return $item->staff_id . '_' . $item->subject_id . '_' . $item->current_class . '_' . $item->admission_year . '_' . $item->section;
            });

        $reportItems = collect();
        $totalClasses = 0;
        $markedClasses = 0;
        $pendingClasses = 0;

        foreach ($teachingBlocks as $block) {
            $sec = trim($block->attendance_section ?? '');
            $key = $block->staff_id . '_' . $block->subject_id . '_' . $block->class_level . '_' . $block->admission_year . '_' . $sec;
            $secKey = $block->staff_id . '_' . $block->subject_id . '_' . $block->admission_year . '_' . $sec;
            $genKey = $block->staff_id . '_' . $block->subject_id . '_' . $block->admission_year;

            $attRecord = $markedAttendance->get($key);

            if (!$attRecord && empty($sec)) {
                $attRecord = $markedAttendance->get($block->staff_id . '_' . $block->subject_id . '_' . $block->class_level . '_' . $block->admission_year . '_');
            }

            $isMarked = !empty($attRecord) && $attRecord->marked_count > 0;

            if (!empty($sec) && isset($hasTimetableForSecKeys[$secKey])) {
                $isScheduledToday = isset($scheduledTodaySecKeys[$secKey]);
            } else {
                $isScheduledToday = isset($scheduledTodayGenKeys[$genKey]);
            }

            // Skip records that are not scheduled today (and not marked today)
            if (!$isScheduledToday && !$isMarked) {
                continue;
            }

            if ($isMarked) {
                $markedClasses++;
            } else {
                $pendingClasses++;
            }
            $totalClasses++;

            $item = (object) [
                'staff_id' => $block->staff_id,
                'teacher_name' => trim(($block->staff_first_name ?? '') . ' ' . ($block->staff_last_name ?? '')),
                'employee_id' => $block->employee_id,
                'subject_id' => $block->subject_id,
                'subject_name' => $block->subject_name,
                'class_level' => $block->class_level,
                'admission_year' => $block->admission_year,
                'section' => $block->attendance_section,
                'assigned_students' => $block->assigned_student_count,
                'is_marked' => $isMarked,
                'is_scheduled_today' => $isScheduledToday,
                'marked_students' => $isMarked ? $attRecord->marked_count : 0,
                'last_marked_at' => $isMarked ? $attRecord->last_marked_at : null,
            ];

            // Apply status filter
            if ($statusFilter === 'marked' && !$isMarked) {
                continue;
            }
            if ($statusFilter === 'pending' && $isMarked) {
                continue;
            }

            $reportItems->push($item);
        }

        $markedPercentage = $totalClasses > 0 ? round(($markedClasses / $totalClasses) * 100, 1) : 0;

        [$isHolidayOrSunday, $holidayReason] = $this->checkIfSundayOrHoliday($date);

        return view('attendance.reports.teacher_status', compact(
            'reportItems',
            'date',
            'dayOfWeek',
            'statusFilter',
            'classFilter',
            'totalClasses',
            'markedClasses',
            'pendingClasses',
            'markedPercentage',
            'isHolidayOrSunday',
            'holidayReason'
        ));
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

        $targetDate = $request->reminders[0]['attendance_date'] ?? null;
        if ($targetDate) {
            [$isBlocked, $blockReason] = $this->checkIfSundayOrHoliday($targetDate);
            if ($isBlocked) {
                return redirect()->back()->with('error', "Cannot send attendance reminder for {$targetDate}: {$blockReason}. No attendance marking is required on holidays or Sundays.");
            }
        }

        $count = 0;
        $message = $request->input('custom_message');

        foreach ($request->reminders as $remData) {
            $section = !empty($remData['section']) ? $remData['section'] : null;

            AttendanceReminder::updateOrCreate(
                [
                    'staff_id' => $remData['staff_id'],
                    'subject_id' => $remData['subject_id'],
                    'class_level' => $remData['class_level'],
                    'admission_year' => $remData['admission_year'],
                    'section' => $section,
                    'attendance_date' => $remData['attendance_date'],
                    'is_used' => false,
                ],
                [
                    'created_by' => $user->id,
                    'message' => $message,
                    'updated_at' => now(),
                ]
            );
            $count++;
        }

        return redirect()->back()->with('success', "Attendance reminder(s) sent successfully to {$count} teacher class assignment(s). One-time attendance marking permission granted to teacher(s).");
    }

    /**
     * Teacher Workload Report
     * Displays teacher name, designation, weekly classes count, and students taught.
     */
    public function teacherWorkload(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdministrativeStaff()) {
            abort(403, 'Unauthorized access to teacher workload report.');
        }

        $search = $request->query('search', '');
        $designationFilter = $request->query('designation_filter', '');
        $bpsFilter = $request->query('bps_filter', '');
        $tierFilter = $request->query('tier_filter', 'all');

        $designations = $this->teacherWorkloadService->getDesignationOptions();
        $bpsList = $this->teacherWorkloadService->getBpsOptions();

        [$staffList, $staffIds] = $this->teacherWorkloadService->getFilteredStaff(
            $search, $designationFilter, $bpsFilter
        );

        [$hsscPeriods, $hePeriods] = $this->teacherWorkloadService->getWeeklyClassCounts($staffIds);
        [$hsscStudents, $heStudents] = $this->teacherWorkloadService->getStudentCounts($staffIds);
        $hsscBlocks = $this->teacherWorkloadService->getHsscTeachingBlocks($staffIds);
        $heBlocks = $this->teacherWorkloadService->getHeTeachingBlocks($staffIds);
        [$hsscBlockSlots, $hsscGenSlots, $heGenSlots] = $this->teacherWorkloadService->getTimetableSlots($staffIds);

        [
            $reportItems,
            $totalScheduledClasses,
            $totalStudentsTaughtSum,
            $avgWeeklyClasses
        ] = $this->teacherWorkloadService->buildWorkloadReport(
            $staffList,
            $hsscPeriods,
            $hePeriods,
            $hsscStudents,
            $heStudents,
            $hsscBlocks,
            $heBlocks,
            $hsscBlockSlots,
            $hsscGenSlots,
            $heGenSlots,
            $tierFilter
        );

        $totalTeachersCount = $reportItems->count();

        return view('attendance.reports.teacher_workload', compact(
            'reportItems',
            'search',
            'designationFilter',
            'bpsFilter',
            'tierFilter',
            'designations',
            'bpsList',
            'totalTeachersCount',
            'totalScheduledClasses',
            'totalStudentsTaughtSum',
            'avgWeeklyClasses'
        ));
    }
}

