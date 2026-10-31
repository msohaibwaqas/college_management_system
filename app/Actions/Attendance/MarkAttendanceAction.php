<?php

namespace App\Actions\Attendance;

use App\Exceptions\AttendanceRestrictedException;
use App\Models\Attendance;
use App\Models\AttendanceReminder;
use App\Models\Datesheet;
use App\Models\Result;
use App\Models\User;
use App\Services\AcademicCalendarService;

class MarkAttendanceAction
{
    public function __construct(
        protected AcademicCalendarService $academicCalendarService
    ) {}

    /**
     * Check if marking attendance is restricted for the given user, subject, date, and class.
     * Returns null if allowed, or an error message string if restricted.
     */
    public function checkRestrictions(
        User $user,
        int $subjectId,
        string $date,
        ?string $class = null,
        ?int $year = null,
        ?string $section = null
    ): ?string {
        // Strict Vacation / Holiday / Sunday lock: No staff member can mark attendance during this period
        [$isOff, $reason] = $this->academicCalendarService->checkIfSundayOrHoliday($date);
        if ($isOff) {
            return "Attendance cannot be marked on {$date} because it is a {$reason}.";
        }

        // Administrative staff can mark without cutoff or reminder restrictions
        if ($user->isAdministrativeStaff()) {
            return null;
        }

        // Regular teachers have date, cutoff, reminder, and timetable schedule restrictions
        if ($user->isTeacher()) {
            $staffId = $user->staff?->id;
            $dayOfWeek = \Carbon\Carbon::parse($date)->format('l');

            // 1. Timetable schedule restriction: verify lecture is scheduled on this day of week
            if ($staffId && $subjectId) {
                $ttQuery = \Illuminate\Support\Facades\DB::table('timetable_entries')
                    ->where('staff_id', $staffId)
                    ->where('subject_id', $subjectId)
                    ->where('is_active', 1);

                if ($year) {
                    $ttQuery->where('admission_year', $year);
                }

                $hasTimetable = $ttQuery->exists();
                if ($hasTimetable) {
                    // Check if test is scheduled on this date in Datesheet
                    $isScheduledTest = false;
                    if ($year && $subjectId) {
                        $isScheduledTest = Datesheet::where('subject_id', $subjectId)
                            ->where('admission_year', $year)
                            ->whereDate('test_date', $date)
                            ->exists();
                    }
                    $hasReminder = AttendanceReminder::hasUnused($staffId, $subjectId, $date, $class, $year, $section);

                    if (!$isScheduledTest && !$hasReminder) {
                        $todayEntries = (clone $ttQuery)->where('day_of_week', $dayOfWeek)->get();

                        if ($todayEntries->isEmpty()) {
                            $scheduledDays = (clone $ttQuery)->pluck('day_of_week')->unique()->implode(', ');
                            $subName = \App\Models\Subject::find($subjectId)?->name ?? 'This subject';
                            return "Attendance cannot be marked on {$dayOfWeek} ({$date}) because {$subName} is only scheduled on {$scheduledDays} in the timetable.";
                        }

                        // If a specific section is provided, ensure this section is scheduled on this day
                        if (!empty($section) && $section !== 'unassigned') {
                            $isSecScheduledToday = $todayEntries->contains(function ($entry) use ($section) {
                                return trim($entry->section) === trim($section);
                            });

                            if (!$isSecScheduledToday) {
                                $secDays = (clone $ttQuery)->where('section', $section)->pluck('day_of_week')->unique()->implode(', ');
                                return "Attendance cannot be marked for Section {$section} on {$dayOfWeek} ({$date}) because Section {$section} is only scheduled on {$secDays} in the timetable.";
                            }
                        }
                    }
                }
            }

            // 2. Past date and 11:00 PM cutoff restrictions
            $today = date('Y-m-d');
            $isPastDate = ($date !== $today);
            $isPastCutoff = ((int) date('H') >= 23);

            if ($isPastDate || $isPastCutoff) {
                $hasReminder = AttendanceReminder::hasUnused($staffId, $subjectId, $date, $class, $year, $section);

                // Allow if a test is scheduled for this subject and batch on this date
                $isScheduledTest = false;
                if ($year && $subjectId) {
                    $isScheduledTest = Datesheet::where('subject_id', $subjectId)
                        ->where('admission_year', $year)
                        ->whereDate('test_date', $date)
                        ->exists();
                }

                if (!$hasReminder && !$isScheduledTest) {
                    if ($date !== $today) {
                        return 'Teachers can only mark attendance for the current date unless granted permission by the Principal via a reminder. If you received a reminder, it may have already been used.';
                    }
                    if ((int) date('H') >= 23) {
                        return 'Attendance marking is closed after 11:00 PM.';
                    }

                    return 'Attendance marking for this date is restricted. If you received a reminder from the Principal, it may have already been used.';
                }
            }
        }

        return null;
    }

    /**
     * Execute attendance marking, perform bulk upsert, and consume any active reminder.
     *
     * @param User $user
     * @param array $data
     * @return int Number of records upserted
     * @throws AttendanceRestrictedException
     */
    public function execute(User $user, array $data): int
    {
        $subjectId = (int) $data['subject_id'];
        $date = $data['attendance_date'];
        $class = $data['class'] ?? null;
        $year = isset($data['year']) ? (int) $data['year'] : null;
        $section = $data['section'] ?? null;

        // Verify restrictions
        $restrictionError = $this->checkRestrictions($user, $subjectId, $date, $class, $year, $section);
        if ($restrictionError !== null) {
            throw new AttendanceRestrictedException($restrictionError);
        }

        $staff = $user->staff;
        $staffId = $staff?->id ?? $user->id;
        $attendance = $data['attendance'] ?? [];
        $remarks = $data['remarks'] ?? [];

        // Check active approved leaves for these students on this date to safeguard against accidental absences
        $activeLeaves = \App\Models\StudentLeave::approved()
            ->forDate($date)
            ->whereIn('student_id', array_keys($attendance))
            ->get()
            ->keyBy('student_id');

        $upsertData = [];
        foreach ($attendance as $studentId => $status) {
            $studentRemarks = $remarks[$studentId] ?? null;

            // If teacher marked absent or leave, but student has institutional approved leave, ensure leave status & remarks
            if (isset($activeLeaves[$studentId])) {
                $leave = $activeLeaves[$studentId];
                if ($status === 'absent') {
                    $status = 'leave';
                    $studentRemarks = $studentRemarks ?: "Approved Leave: {$leave->reason}";
                } elseif ($status === 'leave' && empty($studentRemarks)) {
                    $studentRemarks = "Approved Leave: {$leave->reason}";
                }
            }

            $upsertData[] = [
                'student_id'      => (int) $studentId,
                'subject_id'      => $subjectId,
                'staff_id'        => $staffId,
                'attendance_date' => $date,
                'status'          => $status,
                'remarks'         => $studentRemarks,
            ];
        }

        if (!empty($upsertData)) {
            Attendance::upsert(
                $upsertData,
                ['student_id', 'subject_id', 'attendance_date'],
                ['status', 'remarks', 'staff_id']
            );
        }

        // Sync with Test Attendance & Results if this attendance coincides with a scheduled test
        $scheduledTest = null;
        if (!empty($data['datesheet_id'])) {
            $scheduledTest = Datesheet::find($data['datesheet_id']);
        } elseif ($year && $subjectId && $date) {
            $scheduledTest = Datesheet::where('subject_id', $subjectId)
                ->where('admission_year', $year)
                ->whereDate('test_date', $date)
                ->first();
        }

        if ($scheduledTest) {
            $examName = $data['exam_name'] ?? $scheduledTest->exam_name;
            if (!$examName) {
                $examName = 'Test - ' . \Carbon\Carbon::parse($date)->format('d M Y');
            }
            $examType = $data['exam_type'] ?? self::detectExamType($examName);
            $totalMarks = !empty($data['test_total_marks']) ? (int) $data['test_total_marks'] : 25;
            $classLevel = (string) ($class ?? ($year == 2026 ? '11' : '12'));

            foreach ($attendance as $studentId => $status) {
                $isAbsentFromTest = in_array($status, ['absent', 'leave']);

                $search = [
                    'student_id'    => (int) $studentId,
                    'subject_id'    => $subjectId,
                    'class_level'   => $classLevel,
                    'exam_type'     => $examType,
                    'exam_name'     => $examName,
                    'academic_year' => (int) $year,
                ];

                $existingResult = Result::where($search)->first();

                if ($existingResult) {
                    if ($isAbsentFromTest) {
                        $existingResult->update([
                            'total_marks'        => $totalMarks,
                            'is_absent'          => 1,
                            'obtained_theory'    => 0,
                            'obtained_practical' => 0,
                            'updated_by'         => $user->id,
                        ]);
                    } else {
                        $updateData = [
                            'total_marks' => $totalMarks,
                            'is_absent'   => 0,
                            'updated_by'  => $user->id,
                        ];
                        // If it was previously marked absent with 0 marks, reset marks to null
                        // so present students remain empty in marks entry ready for scoring
                        if ($existingResult->is_absent == 1 && $existingResult->obtained_theory === 0) {
                            $updateData['obtained_theory'] = null;
                            $updateData['obtained_practical'] = null;
                        }
                        $existingResult->update($updateData);
                    }
                } else {
                    Result::create(array_merge($search, [
                        'total_marks'        => $totalMarks,
                        'is_absent'          => $isAbsentFromTest ? 1 : 0,
                        'obtained_theory'    => $isAbsentFromTest ? 0 : null,
                        'obtained_practical' => $isAbsentFromTest ? 0 : null,
                        'created_by'         => $user->id,
                        'updated_by'         => $user->id,
                    ]));
                }
            }

            // Automatically mark enrolled students who were struck off on this test date as ABSENT in test results
            $struckOffStudentsQuery = \App\Models\Student::join('admissions', 'students.id', '=', 'admissions.student_id')
                ->where('admissions.admission_year', $year)
                ->where(function ($q) use ($classLevel) {
                    if ($classLevel) {
                        $q->where('admissions.current_class', $classLevel);
                    }
                })
                ->whereExists(function ($q) use ($date) {
                    $q->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('student_struck_off_logs')
                        ->whereColumn('student_struck_off_logs.student_id', 'students.id')
                        ->whereDate('student_struck_off_logs.struck_off_at', '<=', $date)
                        ->where(function ($sub) use ($date) {
                            $sub->whereNull('student_struck_off_logs.readmitted_at')
                                ->orWhereDate('student_struck_off_logs.readmitted_at', '>', $date);
                        });
                });

            if ($staff?->id) {
                $struckOffStudentsQuery->whereHas('studentTeacherSubjects', function ($q) use ($staff, $subjectId) {
                    $q->where('staff_id', $staff->id)
                      ->where('subject_id', $subjectId);
                });
            } else {
                $struckOffStudentsQuery->where(function ($q) use ($subjectId) {
                    $q->whereHas('compulsorySubjects', function ($sub) use ($subjectId) {
                        $sub->where('subject_id', $subjectId);
                    })->orWhereHas('electives', function ($sub) use ($subjectId) {
                        $sub->where('subject_id', $subjectId);
                    });
                });
            }

            $struckOffStudentIds = $struckOffStudentsQuery->pluck('students.id');

            foreach ($struckOffStudentIds as $struckOffId) {
                $search = [
                    'student_id'    => (int) $struckOffId,
                    'subject_id'    => $subjectId,
                    'class_level'   => $classLevel,
                    'exam_type'     => $examType,
                    'exam_name'     => $examName,
                    'academic_year' => (int) $year,
                ];

                Result::updateOrCreate(
                    $search,
                    [
                        'total_marks'        => $totalMarks,
                        'is_absent'          => 1,
                        'obtained_theory'    => 0,
                        'obtained_practical' => 0,
                        'created_by'         => $user->id,
                        'updated_by'         => $user->id,
                    ]
                );
            }
        }

        // Mark matching reminder as used (one-time exception consumed)
        if ($staff?->id) {
            AttendanceReminder::consume($staff->id, $subjectId, $date, $class, $year, $section);
        }

        return count($upsertData);
    }

    /**
     * Auto-detect exam type key from exam name.
     */
    protected static function detectExamType(?string $name): string
    {
        if (!$name) return 'Oct_test';
        $lower = strtolower($name);
        if (str_contains($lower, 'oct')) return 'Oct_test';
        if (str_contains($lower, 'dec')) return 'Dec_test';
        if (str_contains($lower, 'send') || str_contains($lower, 'pre-board') || str_contains($lower, 'pre board')) return 'Send_up';
        if (str_contains($lower, 'class')) return 'Class_test';
        return 'Oct_test';
    }
}
