<?php

namespace App\Services;

use App\Models\Admissions;
use App\Models\Attendance;
use App\Models\AttendanceReminder;
use App\Models\Subject;
use App\Models\InstituteSetting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service managing HSSC attendance report generation, multi-subject matrix
 * calculations, and daily teacher marking compliance monitoring.
 */
class AttendanceReportService
{
    public function __construct(
        protected AcademicCalendarService $academicCalendarService
    ) {}

    /**
     * Generates data for single-subject HSSC attendance report.
     */
    public function generateSubjectReport(int $year, string $class, Subject $subject, string $startDate, string $endDate, ?int $staffId = null): array
    {
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

        $attendanceData = Attendance::whereIn('student_id', $students->pluck('student_id'))
            ->where('subject_id', $subject->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        $settings = InstituteSetting::getSettings();

        return compact('students', 'attendanceData', 'subject', 'class', 'year', 'startDate', 'endDate', 'settings');
    }

    /**
     * Generates data for Admin Whole Year Multi-Subject Attendance Report Matrix.
     */
    public function generateWholeYearMatrix(int $year, string $class, string $startDate, string $endDate): array
    {
        $students = Admissions::with(['student.father', 'student.contact'])
            ->where('admission_year', $year)
            ->where('current_class', $class)
            ->whereIn('status', ['Active', 'Admitted', 'active', 'admitted'])
            ->get()
            ->sortBy('college_roll_no');

        $studentIds = $students->pluck('student_id');

        // Get unique enrolled subject IDs
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

        $subjects = Subject::whereIn('id', $enrolledSubjectIds)
            ->orderBy('name')
            ->get();

        $subjectIds = $subjects->pluck('id');

        // Delivered count per subject
        $deliveredPerSubject = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->select('subject_id', DB::raw('COUNT(DISTINCT attendance_date) as total_delivered'))
            ->groupBy('subject_id')
            ->pluck('total_delivered', 'subject_id');

        // Attended counts grouped by student and subject
        $attendedMap = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->whereIn('status', ['present', 'late', 'Present', 'Late'])
            ->select('student_id', 'subject_id', DB::raw('COUNT(*) as attended_count'))
            ->groupBy('student_id', 'subject_id')
            ->get()
            ->groupBy('student_id')
            ->map(function($rows) {
                return $rows->pluck('attended_count', 'subject_id');
            });

        // Enrolled lookup map
        $enrolledLookup = [];
        $compulsoryEnrollments = DB::table('student_compulsory_subjects')
            ->whereIn('student_id', $studentIds)
            ->select('student_id', 'subject_id')
            ->get();

        foreach ($compulsoryEnrollments as $enr) {
            $enrolledLookup[$enr->student_id][$enr->subject_id] = true;
        }

        $electiveEnrollments = DB::table('student_elective_subjects')
            ->whereIn('student_id', $studentIds)
            ->whereNull('deleted_at')
            ->select('student_id', 'subject_id')
            ->get();

        foreach ($electiveEnrollments as $enr) {
            $enrolledLookup[$enr->student_id][$enr->subject_id] = true;
        }

        $teacherEnrollments = DB::table('student_teacher_subjects')
            ->whereIn('student_id', $studentIds)
            ->whereNull('deleted_at')
            ->select('student_id', 'subject_id')
            ->get();

        foreach ($teacherEnrollments as $enr) {
            $enrolledLookup[$enr->student_id][$enr->subject_id] = true;
        }

        $settings = InstituteSetting::getSettings();

        return compact(
            'students',
            'subjects',
            'deliveredPerSubject',
            'attendedMap',
            'enrolledLookup',
            'class',
            'year',
            'startDate',
            'endDate',
            'settings'
        );
    }

    /**
     * Builds teacher marking compliance status for a specific date.
     */
    public function buildTeacherStatus(string $date, string $statusFilter = 'all', string $classFilter = ''): array
    {
        $dayOfWeek = Carbon::parse($date)->format('l');

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

        // Fetch all active timetable entries to check if section has timetable config
        $ttAll = DB::table('timetable_entries')
            ->where('is_active', 1)
            ->select('staff_id', 'subject_id', 'admission_year', 'section')
            ->get();

        $hasTimetableForSecKeys = [];
        $hasTimetableForGenKeys = [];
        $staffSubjectHasSectionsInTt = [];
        foreach ($ttAll as $entry) {
            $genKey = $entry->staff_id . '_' . $entry->subject_id . '_' . $entry->admission_year;
            $hasTimetableForGenKeys[$genKey] = true;
            if (!empty($entry->section)) {
                $secKey = $genKey . '_' . trim($entry->section);
                $hasTimetableForSecKeys[$secKey] = true;
                $staffSubjectHasSectionsInTt[$genKey] = true;
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

        // Check attendance records for selected date
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

            $hasTimetableForBlock = isset($hasTimetableForGenKeys[$genKey]);

            if ($hasTimetableForBlock) {
                if (!empty($sec) && isset($hasTimetableForSecKeys[$secKey])) {
                    $isScheduledToday = isset($scheduledTodaySecKeys[$secKey]);
                } else {
                    $hasAnySecInTt = isset($staffSubjectHasSectionsInTt[$genKey]);
                    if ($hasAnySecInTt && !empty($sec)) {
                        $isScheduledToday = isset($scheduledTodaySecKeys[$secKey]);
                    } elseif ($hasAnySecInTt && empty($sec)) {
                        // Timetable is divided into sections, but this block has no section assigned
                        $isScheduledToday = false;
                    } else {
                        $isScheduledToday = isset($scheduledTodayGenKeys[$genKey]);
                    }
                }
            } else {
                // If no timetable has been configured for this subject/staff/year, treat as scheduled
                $isScheduledToday = true;
            }

            // Strictly filter out any class/section not scheduled on this day
            if (!$isScheduledToday) {
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

            if ($statusFilter === 'marked' && !$isMarked) continue;
            if ($statusFilter === 'pending' && $isMarked) continue;

            $reportItems->push($item);
        }

        $markedPercentage = $totalClasses > 0 ? round(($markedClasses / $totalClasses) * 100, 1) : 0;
        [$isHolidayOrSunday, $holidayReason] = $this->academicCalendarService->checkIfSundayOrHoliday($date);

        return compact(
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
        );
    }

    /**
     * Persists attendance reminders for teachers.
     */
    public function sendReminders(array $reminders, ?string $customMessage, int $userId): array
    {
        $targetDate = $reminders[0]['attendance_date'] ?? null;
        if ($targetDate) {
            [$isBlocked, $blockReason] = $this->academicCalendarService->checkIfSundayOrHoliday($targetDate);
            if ($isBlocked) {
                return [
                    'success' => false,
                    'message' => "Cannot send attendance reminder for {$targetDate}: {$blockReason}. No attendance marking is required on holidays or Sundays."
                ];
            }
        }

        $count = 0;
        foreach ($reminders as $remData) {
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
                    'created_by' => $userId,
                    'custom_message' => $customMessage,
                    'updated_at' => now(),
                ]
            );
            $count++;
        }

        return [
            'success' => true,
            'count' => $count
        ];
    }
}
