<?php

namespace App\Services;

use App\Models\Admissions;
use App\Models\Attendance;
use App\Models\StruckOffSetting;
use App\Models\StudentStruckOffLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StruckOffService
{
    /**
     * Get active settings.
     */
    public function getSettings(): StruckOffSetting
    {
        return StruckOffSetting::getSettings();
    }

    /**
     * Struck off an eligible student according to college policy.
     *
     * @throws InvalidArgumentException
     */
    public function strikeOff(
        int $studentId,
        int $subjectId,
        int $recorderStaffId,
        ?string $reason = null,
        ?string $month = null
    ): StudentStruckOffLog {
        $settings = $this->getSettings();
        $threshold = (int) $settings->absent_threshold;
        if ($threshold < 1) {
            $threshold = 20;
        }

        // Strict monthly enforcement: strike-off cannot be based on whole year
        $month = ($month && $month !== 'all') ? $month : now()->format('Y-m');

        // Check if student has previous readmission - only absences AFTER latest readmission count
        $latestReadmit = StudentStruckOffLog::where('student_id', $studentId)
            ->where('status', 'readmitted')
            ->latest('readmitted_at')
            ->first();

        // Verify absence count meets policy threshold for the specified month
        $attQuery = Attendance::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('status', 'absent')
            ->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$month]);

        if ($latestReadmit) {
            $lastDate = $latestReadmit->readmitted_at
                ? $latestReadmit->readmitted_at->format('Y-m-d')
                : ($latestReadmit->struck_off_at ? $latestReadmit->struck_off_at->format('Y-m-d') : null);
            if ($lastDate) {
                $attQuery->where('attendance_date', '>', $lastDate);
            }
        }

        $absenceCount = $attQuery->count();
        if ($absenceCount < $threshold) {
            throw new InvalidArgumentException(
                "Student has only {$absenceCount} absent(s) in this subject for {$month} (since last readmission), which is below the required policy threshold of {$threshold} to strike off."
            );
        }

        // Check if already struck off
        $alreadyStruckOff = StudentStruckOffLog::where('student_id', $studentId)
            ->where('status', 'struck_off')
            ->exists();

        if ($alreadyStruckOff) {
            throw new InvalidArgumentException("This student is already struck off. They must be readmitted before being struck off again.");
        }

        // Get active admission
        $admission = Admissions::where('student_id', $studentId)
            ->whereIn('status', ['Active', 'active', 'Admitted', 'admitted'])
            ->first();

        if (!$admission) {
            throw new InvalidArgumentException("No active admission record found for this student.");
        }

        return DB::transaction(function () use ($studentId, $admission, $recorderStaffId, $subjectId, $absenceCount, $reason) {
            $log = StudentStruckOffLog::create([
                'student_id'              => $studentId,
                'admission_id'            => $admission->id,
                'struck_off_by'           => $recorderStaffId,
                'subject_id'              => $subjectId,
                'absence_count_at_strike' => $absenceCount,
                'struck_off_at'           => now(),
                'struck_off_reason'       => $reason,
                'academic_year'           => $admission->academic_year ?? (int) date('Y'),
                'status'                  => 'struck_off',
            ]);

            $admission->strikeOff();

            return $log;
        });
    }

    /**
     * Readmit a struck off student if eligible under college limits and role policy.
     *
     * Policy Rules:
     * - 1st strike-off: Striking teacher or Admin / Clerk.
     * - 2nd+ strike-off: ONLY Admin and Clerk can readmit.
     *
     * @throws InvalidArgumentException
     */
    public function readmit(StudentStruckOffLog $log, int $userId, ?string $note = null): void
    {
        if (!$log->isStruckOff()) {
            throw new InvalidArgumentException("This student is not currently struck off.");
        }

        $settings = $this->getSettings();
        $maxAllowed = (int) $settings->max_readmissions_per_year;

        if (!$log->canBeReadmitted($maxAllowed)) {
            $count = $log->getReadmissionsCountForYear();
            throw new InvalidArgumentException("This student has already been readmitted {$count} times this academic year. Maximum {$maxAllowed} readmissions per year are allowed according to college policy.");
        }

        $user = User::find($userId);
        if (!$log->canUserReadmit($user, $maxAllowed)) {
            $reason = $log->getReadmissionRestrictionReason($user, $maxAllowed)
                ?? "You are not authorized to readmit this student.";
            throw new InvalidArgumentException($reason);
        }

        DB::transaction(function () use ($log, $userId, $note) {
            $log->markReadmitted($userId, $note);
            $log->admission?->activate($userId);
        });
    }

    /**
     * Bulk readmit all eligible currently struck-off students.
     *
     * @return int Number of students readmitted
     */
    public function bulkReadmit(int $userId, ?string $note = null): int
    {
        $settings = $this->getSettings();
        $maxAllowed = (int) $settings->max_readmissions_per_year;

        $logs = StudentStruckOffLog::where('status', 'struck_off')->get();
        $count = 0;

        DB::transaction(function () use ($logs, $userId, $note, $maxAllowed, &$count) {
            foreach ($logs as $log) {
                if ($log->canBeReadmitted($maxAllowed)) {
                    $log->markReadmitted($userId, $note ?? 'Bulk readmitted as per policy reset');
                    $log->admission?->activate($userId);
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Update institutional settings.
     */
    public function updateSettings(int $threshold, int $maxReadmissions, string $calculationPeriod): StruckOffSetting
    {
        $settings = $this->getSettings();
        $settings->update([
            'absent_threshold'          => $threshold,
            'max_readmissions_per_year' => $maxReadmissions,
            'calculation_period'        => $calculationPeriod,
        ]);

        return $settings;
    }
/**
     * Get teacher panel view data with assigned subjects, eligible students, and months.
     */
    public function getTeacherPanelData(\Illuminate\Http\Request $request, ?int $staffId, $staff): array
    {
        $settings = $this->getSettings();
        $threshold = (int) $settings->absent_threshold;
        if ($threshold < 1) {
            $threshold = 20;
        }

        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $currentMonth = now()->format('Y-m');
        $month = $request->query('month', $currentMonth);
        if (!$month || $month === 'all') {
            $month = $currentMonth;
        }

        if ($request->filled('class_subject') && str_contains($request->class_subject, ':')) {
            $parts     = explode(':', $request->class_subject);
            $subjectId = (int) ($parts[0] ?? 0);
            $class     = $parts[1] ?? null;
            $optYear   = (int) ($parts[2] ?? 0);
            if (!$year && $optYear > 0) {
                $year = $optYear;
            }
        } else {
            $subjectId = $request->filled('subject_id') ? (int) $request->query('subject_id') : null;
            $class     = $request->query('class');
        }

        $subjectsQuery = DB::table('student_teacher_subjects')
            ->join('subjects', 'student_teacher_subjects.subject_id', '=', 'subjects.id')
            ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
            ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->whereNotIn('admissions.status', ['Struck Off', 'struck_off', 'Graduated'])
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('admissions.deleted_at');

        if ($staffId) {
            $subjectsQuery->where('student_teacher_subjects.staff_id', $staffId);
        }

        $mySubjects = $subjectsQuery->select(
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'admissions.current_class as current_class',
                'admissions.admission_year as admission_year',
                DB::raw('COUNT(DISTINCT student_teacher_subjects.student_id) as student_count')
            )
            ->groupBy('subjects.id', 'subjects.name', 'admissions.current_class', 'admissions.admission_year')
            ->orderByDesc('admissions.admission_year')
            ->orderBy('admissions.current_class')
            ->orderBy('subjects.name')
            ->get();

        $availableYears = $mySubjects->pluck('admission_year')->filter()->unique()->values()->sortDesc();

        if (!$year && $availableYears->isNotEmpty()) {
            $year = (int) $availableYears->first();
        }

        $subjectsForYear = $mySubjects->where('admission_year', $year)->values();

        $validCurrentSubject = $subjectsForYear->first(function ($item) use ($subjectId, $class) {
            return (int) $item->subject_id === (int) $subjectId && (!$class || $item->current_class == $class);
        });

        if ($validCurrentSubject) {
            $subjectId = (int) $validCurrentSubject->subject_id;
            $class     = $validCurrentSubject->current_class;
        } elseif ($subjectsForYear->isNotEmpty()) {
            $firstSub  = $subjectsForYear->first();
            $subjectId = (int) $firstSub->subject_id;
            $class     = $firstSub->current_class;
        } else {
            $subjectId = null;
            $class     = null;
        }

        $availableMonths = DB::table('attendances')
            ->select(DB::raw("DATE_FORMAT(attendance_date, '%Y-%m') as m"))
            ->distinct()
            ->orderByDesc('m')
            ->limit(12)
            ->pluck('m');

        if (!$availableMonths->contains($currentMonth)) {
            $availableMonths->prepend($currentMonth);
        }

        $eligibleStudents = collect();

        if ($subjectId && $year) {
            $latestReadmitSub = DB::table('student_struck_off_logs')
                ->select('student_id', DB::raw('MAX(DATE(COALESCE(readmitted_at, struck_off_at))) as last_readmitted_date'))
                ->where('status', 'readmitted')
                ->groupBy('student_id');

            $attQuery = DB::table('attendances')
                ->leftJoinSub($latestReadmitSub, 'lr', function ($join) {
                    $join->on('attendances.student_id', '=', 'lr.student_id');
                })
                ->where('attendances.subject_id', $subjectId)
                ->where('attendances.status', 'absent')
                ->whereRaw("DATE_FORMAT(attendances.attendance_date, '%Y-%m') = ?", [$month])
                ->where(function ($q) {
                    $q->whereNull('lr.last_readmitted_date')
                      ->orWhereRaw('attendances.attendance_date > lr.last_readmitted_date');
                })
                ->select(
                    'attendances.student_id',
                    DB::raw('COUNT(*) as absence_count')
                )
                ->groupBy('attendances.student_id')
                ->having('absence_count', '>=', $threshold);

            $assignedStudentsQuery = DB::table('student_teacher_subjects')
                ->where('subject_id', $subjectId)
                ->whereNull('deleted_at');

            if ($staffId) {
                $assignedStudentsQuery->where('staff_id', $staffId);
            }

            $eligibleStudents = DB::table('students')
                ->join('admissions', 'students.id', '=', 'admissions.student_id')
                ->joinSub($assignedStudentsQuery, 'sts', 'students.id', '=', 'sts.student_id')
                ->joinSub($attQuery, 'abs', 'students.id', '=', 'abs.student_id')
                ->leftJoin('fathers', 'students.id', '=', 'fathers.student_id')
                ->where('admissions.admission_year', $year)
                ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
                ->whereNotIn('admissions.status', ['Struck Off', 'struck_off', 'Graduated'])
                ->whereNull('admissions.deleted_at')
                ->when($class, function ($q) use ($class) {
                    $q->where('admissions.current_class', $class);
                })
                ->select(
                    'students.id as student_id',
                    'students.first_name',
                    'students.last_name',
                    'fathers.name as father_name',
                    'admissions.college_roll_no',
                    'admissions.college_roll_no as roll_number',
                    'admissions.current_class',
                    'admissions.admission_year',
                    'abs.absence_count'
                )
                ->distinct()
                ->orderByDesc('abs.absence_count')
                ->get();

            $struckOffStudentIds = StudentStruckOffLog::where('status', 'struck_off')
                ->whereIn('student_id', $eligibleStudents->pluck('student_id'))
                ->pluck('student_id')
                ->toArray();

            foreach ($eligibleStudents as $s) {
                $s->already_struck_off = in_array($s->student_id, $struckOffStudentIds);
            }
        }

        return [
            'mySubjects' => $mySubjects,
            'subjectsForYear' => $subjectsForYear,
            'eligibleStudents' => $eligibleStudents,
            'selectedSubjectId' => $subjectId,
            'selectedYear' => $year,
            'selectedClass' => $class,
            'selectedMonth' => $month,
            'selectedThreshold' => $threshold,
            'availableYears' => $availableYears,
            'availableMonths' => $availableMonths,
            'settings' => $settings,
            'staff' => $staff,
        ];
    }

    /**
     * Get struck-off logs index data with filters and aggregates.
     */
    public function getIndexData(\Illuminate\Http\Request $request, $user): array
    {
        $settings   = $this->getSettings();
        $isTeacher  = $user->isTeacher();

        $query = StudentStruckOffLog::with([
                'student',
                'struckOffBy',
                'subject',
                'readmittedBy',
                'admission',
            ])
            ->orderByDesc('struck_off_at');

        if ($isTeacher) {
            $staffId = $user->staff?->id ?? ($user->staff_id ?? 0);
            $query->where('struck_off_by', $staffId);
        }

        $statusFilter = $request->query('status', 'struck_off');
        if ($statusFilter === 'all') {
            // all
        } elseif ($statusFilter === 'readmitted') {
            $query->where('status', 'readmitted');
        } else {
            $query->where('status', 'struck_off');
        }

        if ($request->filled('year')) {
            $query->where('academic_year', $request->year);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $perPage = (int) $request->get('per_page', 30);
        if ($perPage < 1) $perPage = 30;

        $logs = $query->paginate($perPage)->withQueryString();

        $yearsQuery = StudentStruckOffLog::select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year');

        if ($isTeacher) {
            $staffId = $user->staff?->id ?? ($user->staff_id ?? 0);
            $yearsQuery->where('struck_off_by', $staffId);
        }

        $availableYears = $yearsQuery->pluck('academic_year');
        $activeStruckOffCount = StudentStruckOffLog::where('status', 'struck_off')->count();

        return compact('logs', 'statusFilter', 'availableYears', 'settings', 'isTeacher', 'activeStruckOffCount');
    }

    /**
     * Get struck-off history data for a student.
     */
    public function getStudentHistoryData(\App\Models\Student $student): array
    {
        $settings = $this->getSettings();

        $logs = StudentStruckOffLog::where('student_id', $student->id)
            ->with(['struckOffBy', 'subject', 'readmittedBy'])
            ->orderByDesc('struck_off_at')
            ->get();

        $totalStruckOff       = $logs->count();
        $currentYear          = date('Y');
        $struckOffThisYear    = $logs->where('academic_year', $currentYear)->count();
        $readmissionsThisYear = $logs->where('academic_year', $currentYear)->where('status', 'readmitted')->count();

        return compact('student', 'logs', 'totalStruckOff', 'struckOffThisYear', 'readmissionsThisYear', 'settings');
    }
}
