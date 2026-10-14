<?php

namespace App\Http\Controllers;

use App\Models\Admissions;
use App\Models\Attendance;
use App\Models\AttendanceReminder;
use App\Models\Subject;
use App\Models\InstituteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceReportController extends Controller
{
    /**
     * Show attendance report form for HSSC
     */
    public function index()
    {
        $user = auth()->user();
        $isTeacher = $user->hasRole('teacher') && !$user->is_super_admin && !$user->hasRole('admin');
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
            $admissionYears = Admissions::select('admission_year')
                ->distinct()
                ->orderByDesc('admission_year')
                ->pluck('admission_year');

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
        $isTeacher = $user->hasRole('teacher') && !$user->is_super_admin && !$user->hasRole('admin');
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
        if (!$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal') && !$user->hasRole('hssc_admin')) {
            abort(403, 'Unauthorized access to admin attendance reports.');
        }

        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        return view('attendance.reports.whole_year_form', compact('admissionYears'));
    }

    /**
     * Generate Admin Whole Year Multi-Subject Attendance Report Matrix
     */
    public function generateWholeYearReport(Request $request)
    {
        $user = auth()->user();
        if (!$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal') && !$user->hasRole('hssc_admin')) {
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
        if (!$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal') && !$user->hasRole('hssc_admin')) {
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
        $carbonDate = \Carbon\Carbon::parse($dateString);

        // 1. Sunday check
        if ($carbonDate->isSunday()) {
            return [true, "Sunday (Weekly Off Day)"];
        }

        // 2. Public Holidays table check
        $publicHoliday = \App\Models\PublicHoliday::whereDate('date', $dateString)->where('is_active', true)->first();
        if ($publicHoliday) {
            return [true, "Public Holiday: {$publicHoliday->title}"];
        }

        // 3. Announcements table check (is_holiday = true or title/body contains holiday)
        $holidayAnnouncement = \App\Models\Announcement::where(function($q) use ($dateString) {
            $q->where(function($sub) use ($dateString) {
                $sub->where('is_holiday', true)
                    ->whereDate('holiday_date', $dateString);
            })->orWhere(function($sub) use ($dateString) {
                $sub->where(function($kw) {
                    $kw->where('title', 'LIKE', '%holiday%')
                       ->orWhere('body', 'LIKE', '%holiday%')
                       ->orWhere('title', 'LIKE', '%vacation%')
                       ->orWhere('title', 'LIKE', '%off day%');
                })
                ->where(function($dt) use ($dateString) {
                    $dt->whereDate('holiday_date', $dateString)
                       ->orWhereDate('scheduled_at', $dateString)
                       ->orWhereDate('created_at', $dateString);
                });
            });
        })->first();

        if ($holidayAnnouncement) {
            return [true, "Announced Holiday: {$holidayAnnouncement->title}"];
        }

        return [false, null];
    }

    /**
     * Send bulk attendance reminders to teachers for pending attendance marking.
     */
    public function sendReminders(Request $request)
    {
        $user = auth()->user();
        if (!$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal') && !$user->hasRole('hssc_admin')) {
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
        if (!$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal') && !$user->hasRole('hssc_admin')) {
            abort(403, 'Unauthorized access to teacher workload report.');
        }

        $search = $request->query('search', '');
        $designationFilter = $request->query('designation_filter', '');
        $bpsFilter = $request->query('bps_filter', '');
        $tierFilter = $request->query('tier_filter', 'all');

        // Fetch designation options for filter dropdown
        $designations = DB::table('staff')
            ->whereNull('deleted_at')
            ->whereNotNull('designation')
            ->where('designation', '!=', '')
            ->distinct()
            ->orderBy('designation')
            ->pluck('designation');

        // Fetch BPS options for filter dropdown
        $bpsList = DB::table('staff')
            ->whereNull('deleted_at')
            ->whereNotNull('bps')
            ->distinct()
            ->orderBy('bps')
            ->pluck('bps');

        // Base Staff Query: Fetch teaching staff or staff involved in timetable/student assignments
        $staffQuery = DB::table('staff')
            ->whereNull('deleted_at')
            ->where(function($q) {
                $q->where('category', 'teaching')
                  ->orWhereIn('id', function($sub) {
                      $sub->select('staff_id')->from('student_teacher_subjects')->whereNull('deleted_at');
                  })
                  ->orWhereIn('id', function($sub) {
                      $sub->select('staff_id')->from('timetable_entries')->where('is_active', 1);
                  })
                  ->orWhereIn('id', function($sub) {
                      $sub->select('staff_id')->from('he_teacher_assignments');
                  });
            });

        if (!empty($search)) {
            $staffQuery->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%");
            });
        }

        if (!empty($designationFilter)) {
            $staffQuery->where('designation', $designationFilter);
        }

        if (!empty($bpsFilter)) {
            $staffQuery->where('bps', $bpsFilter);
        }

        $staffList = $staffQuery->orderBy('first_name')->orderBy('last_name')->get();
        $staffIds = $staffList->pluck('id');

        // 1. Weekly Classes Aggregation (HSSC + HE/BS)
        $hsscPeriods = DB::table('timetable_entries')
            ->whereIn('staff_id', $staffIds)
            ->where('is_active', 1)
            ->select('staff_id', DB::raw('COUNT(*) as total_classes'))
            ->groupBy('staff_id')
            ->pluck('total_classes', 'staff_id');

        $hePeriods = DB::table('he_timetables')
            ->whereIn('staff_id', $staffIds)
            ->select('staff_id', DB::raw('COUNT(*) as total_classes'))
            ->groupBy('staff_id')
            ->pluck('total_classes', 'staff_id');

        // 2. Students Taught Aggregation (HSSC)
        $hsscStudents = DB::table('student_teacher_subjects')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->whereIn('student_teacher_subjects.staff_id', $staffIds)
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('students.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->select('student_teacher_subjects.staff_id', 'student_teacher_subjects.student_id')
            ->get()
            ->groupBy('staff_id');

        // 3. Students Taught Aggregation (HE / BS)
        $heStudents = DB::table('he_teacher_assignments')
            ->join('he_admissions', 'he_teacher_assignments.he_admission_id', '=', 'he_admissions.id')
            ->whereIn('he_teacher_assignments.staff_id', $staffIds)
            ->whereNull('he_admissions.deleted_at')
            ->whereIn('he_admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->select('he_teacher_assignments.staff_id', 'he_teacher_assignments.he_admission_id')
            ->get()
            ->groupBy('staff_id');

        // 4. HSSC Teaching Blocks (Subjects & Sections Breakdown)
        $hsscBlocks = DB::table('student_teacher_subjects')
            ->join('subjects', 'student_teacher_subjects.subject_id', '=', 'subjects.id')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->leftJoin('student_subject_sections', function($join) {
                $join->on('student_teacher_subjects.student_id', '=', 'student_subject_sections.student_id')
                     ->on('student_teacher_subjects.subject_id', '=', 'student_subject_sections.subject_id')
                     ->on('admissions.admission_year', '=', 'student_subject_sections.admission_year')
                     ->whereNull('student_subject_sections.deleted_at');
            })
            ->whereIn('student_teacher_subjects.staff_id', $staffIds)
            ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('students.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->select(
                'student_teacher_subjects.staff_id',
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'admissions.current_class as class_level',
                'admissions.admission_year',
                'student_subject_sections.section as section',
                DB::raw('COUNT(DISTINCT student_teacher_subjects.student_id) as student_count')
            )
            ->groupBy(
                'student_teacher_subjects.staff_id',
                'subjects.id',
                'subjects.name',
                'subjects.code',
                'admissions.current_class',
                'admissions.admission_year',
                'student_subject_sections.section'
            )
            ->get()
            ->groupBy('staff_id');

        // 5. HE / BS Teaching Blocks (Subjects Breakdown)
        $heBlocks = DB::table('he_teacher_assignments')
            ->join('bs_subjects', 'he_teacher_assignments.bs_subject_id', '=', 'bs_subjects.id')
            ->join('he_admissions', 'he_teacher_assignments.he_admission_id', '=', 'he_admissions.id')
            ->leftJoin('programs', 'he_admissions.program_id', '=', 'programs.id')
            ->whereIn('he_teacher_assignments.staff_id', $staffIds)
            ->whereIn('he_admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->whereNull('he_admissions.deleted_at')
            ->select(
                'he_teacher_assignments.staff_id',
                'bs_subjects.id as bs_subject_id',
                'bs_subjects.name as subject_name',
                'bs_subjects.code as subject_code',
                'programs.name as program_name',
                'he_admissions.current_semester as semester_no',
                DB::raw('COUNT(DISTINCT he_teacher_assignments.he_admission_id) as student_count')
            )
            ->groupBy(
                'he_teacher_assignments.staff_id',
                'bs_subjects.id',
                'bs_subjects.name',
                'bs_subjects.code',
                'programs.name',
                'he_admissions.current_semester'
            )
            ->get()
            ->groupBy('staff_id');

        // 6. Timetable Slot Counts & Lecture Slot Numbers per Block / Subject
        $hsscBlockSlots = DB::table('timetable_entries')
            ->join('time_slots', 'timetable_entries.time_slot_id', '=', 'time_slots.id')
            ->whereIn('timetable_entries.staff_id', $staffIds)
            ->where('timetable_entries.is_active', 1)
            ->select(
                'timetable_entries.staff_id',
                'timetable_entries.subject_id',
                'timetable_entries.admission_year',
                DB::raw("COALESCE(timetable_entries.section, '') as section"),
                DB::raw('COUNT(*) as slots_count'),
                DB::raw("GROUP_CONCAT(DISTINCT time_slots.slot_number ORDER BY time_slots.slot_number ASC SEPARATOR ', ') as slot_numbers"),
                DB::raw("GROUP_CONCAT(DISTINCT time_slots.name ORDER BY time_slots.slot_number ASC SEPARATOR ', ') as slot_names")
            )
            ->groupBy('timetable_entries.staff_id', 'timetable_entries.subject_id', 'timetable_entries.admission_year', DB::raw("COALESCE(timetable_entries.section, '')"))
            ->get()
            ->keyBy(function($item) {
                return $item->staff_id . '_' . $item->subject_id . '_' . $item->admission_year . '_' . trim($item->section);
            });

        $hsscGenSlots = DB::table('timetable_entries')
            ->join('time_slots', 'timetable_entries.time_slot_id', '=', 'time_slots.id')
            ->whereIn('timetable_entries.staff_id', $staffIds)
            ->where('timetable_entries.is_active', 1)
            ->select(
                'timetable_entries.staff_id',
                'timetable_entries.subject_id',
                'timetable_entries.admission_year',
                DB::raw('COUNT(*) as slots_count'),
                DB::raw("GROUP_CONCAT(DISTINCT time_slots.slot_number ORDER BY time_slots.slot_number ASC SEPARATOR ', ') as slot_numbers"),
                DB::raw("GROUP_CONCAT(DISTINCT time_slots.name ORDER BY time_slots.slot_number ASC SEPARATOR ', ') as slot_names")
            )
            ->groupBy('timetable_entries.staff_id', 'timetable_entries.subject_id', 'timetable_entries.admission_year')
            ->get()
            ->keyBy(function($item) {
                return $item->staff_id . '_' . $item->subject_id . '_' . $item->admission_year;
            });

        $heGenSlots = DB::table('he_timetables')
            ->whereIn('staff_id', $staffIds)
            ->select(
                'staff_id',
                'bs_subject_id',
                DB::raw('COUNT(*) as slots_count'),
                DB::raw("GROUP_CONCAT(DISTINCT period_number ORDER BY period_number ASC SEPARATOR ', ') as slot_numbers")
            )
            ->groupBy('staff_id', 'bs_subject_id')
            ->get()
            ->keyBy(function($item) {
                return $item->staff_id . '_' . $item->bs_subject_id;
            });

        $reportItems = collect();
        $totalScheduledClasses = 0;
        $totalStudentsTaughtSum = 0;

        foreach ($staffList as $stf) {
            $hsscWk = $hsscPeriods->get($stf->id, 0);
            $heWk = $hePeriods->get($stf->id, 0);
            $weeklyClasses = $hsscWk + $heWk;

            $hsscStdCount = $hsscStudents->has($stf->id) ? $hsscStudents[$stf->id]->pluck('student_id')->unique()->count() : 0;
            $heStdCount = $heStudents->has($stf->id) ? $heStudents[$stf->id]->pluck('he_admission_id')->unique()->count() : 0;
            $totalStudentsTaught = $hsscStdCount + $heStdCount;

            $assignedBlocks = collect();

            if ($hsscBlocks->has($stf->id)) {
                foreach ($hsscBlocks[$stf->id] as $hb) {
                    $sec = trim($hb->section ?? '');
                    $secStr = !empty($sec) ? "Sec {$sec}" : "All Secs";

                    $exactKey = $stf->id . '_' . $hb->subject_id . '_' . $hb->admission_year . '_' . $sec;
                    $genKey = $stf->id . '_' . $hb->subject_id . '_' . $hb->admission_year;

                    if (isset($hsscBlockSlots[$exactKey])) {
                        $slots = $hsscBlockSlots[$exactKey]->slots_count;
                        $slotNos = $hsscBlockSlots[$exactKey]->slot_numbers;
                        $slotNames = $hsscBlockSlots[$exactKey]->slot_names;
                    } elseif (isset($hsscGenSlots[$genKey])) {
                        $slots = $hsscGenSlots[$genKey]->slots_count;
                        $slotNos = $hsscGenSlots[$genKey]->slot_numbers;
                        $slotNames = $hsscGenSlots[$genKey]->slot_names;
                    } else {
                        $slots = 0;
                        $slotNos = '';
                        $slotNames = '';
                    }

                    $assignedBlocks->push((object)[
                        'type' => 'HSSC',
                        'subject_id' => $hb->subject_id,
                        'subject_name' => $hb->subject_name,
                        'subject_code' => $hb->subject_code,
                        'description' => "{$hb->class_level}th Class ({$hb->admission_year}) - {$secStr}",
                        'student_count' => $hb->student_count,
                        'slots_count' => $slots,
                        'slot_numbers' => $slotNos,
                        'slot_names' => $slotNames,
                    ]);
                }
            }

            if ($heBlocks->has($stf->id)) {
                foreach ($heBlocks[$stf->id] as $heb) {
                    $progStr = !empty($heb->program_name) ? $heb->program_name : "BS/ADP";
                    $heKey = $stf->id . '_' . $heb->bs_subject_id;
                    $slots = isset($heGenSlots[$heKey]) ? $heGenSlots[$heKey]->slots_count : 0;
                    $slotNos = isset($heGenSlots[$heKey]) ? $heGenSlots[$heKey]->slot_numbers : '';
                    $slotNames = !empty($slotNos) ? "Lecture {$slotNos}" : '';

                    $assignedBlocks->push((object)[
                        'type' => 'BS/ADP',
                        'subject_id' => $heb->bs_subject_id,
                        'subject_name' => $heb->subject_name,
                        'subject_code' => $heb->subject_code,
                        'description' => "{$progStr} (Sem {$heb->semester_no})",
                        'student_count' => $heb->student_count,
                        'slots_count' => $slots,
                        'slot_numbers' => $slotNos,
                        'slot_names' => $slotNames,
                    ]);
                }
            }

            // Workload tier categorization
            if ($weeklyClasses >= 25) {
                $workloadTier = 'high';
            } elseif ($weeklyClasses >= 15) {
                $workloadTier = 'moderate';
            } elseif ($weeklyClasses > 0) {
                $workloadTier = 'light';
            } else {
                $workloadTier = 'unassigned';
            }

            // Apply tier filter
            if ($tierFilter === 'high' && $workloadTier !== 'high') continue;
            if ($tierFilter === 'moderate' && $workloadTier !== 'moderate') continue;
            if ($tierFilter === 'light' && $workloadTier !== 'light') continue;
            if ($tierFilter === 'unassigned' && $workloadTier !== 'unassigned') continue;

            $totalScheduledClasses += $weeklyClasses;
            $totalStudentsTaughtSum += $totalStudentsTaught;

            $teacherName = trim(($stf->first_name ?? '') . ' ' . ($stf->last_name ?? ''));

            $reportItems->push((object)[
                'staff_id' => $stf->id,
                'employee_id' => $stf->staff_id,
                'teacher_name' => $teacherName,
                'designation' => $stf->designation ?? 'N/A',
                'bps' => $stf->bps ?? null,
                'category' => $stf->category ?? 'teaching',
                'weekly_classes' => $weeklyClasses,
                'hssc_weekly_classes' => $hsscWk,
                'he_weekly_classes' => $heWk,
                'total_students_taught' => $totalStudentsTaught,
                'hssc_students_count' => $hsscStdCount,
                'he_students_count' => $heStdCount,
                'assigned_blocks' => $assignedBlocks,
                'subjects_count' => $assignedBlocks->pluck('subject_name')->unique()->count(),
                'workload_tier' => $workloadTier,
            ]);
        }

        $totalTeachersCount = $reportItems->count();
        $avgWeeklyClasses = $totalTeachersCount > 0 ? round($totalScheduledClasses / $totalTeachersCount, 1) : 0;

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

