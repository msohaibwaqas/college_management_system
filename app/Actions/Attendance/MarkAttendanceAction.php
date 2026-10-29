<?php

namespace App\Actions\Attendance;

use App\Exceptions\AttendanceRestrictedException;
use App\Models\Attendance;
use App\Models\AttendanceReminder;
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

        // Regular teachers have date, cutoff, and reminder restrictions
        if ($user->isTeacher()) {
            $today = date('Y-m-d');
            $isPastDate = ($date !== $today);
            $isPastCutoff = ((int) date('H') >= 23);

            if ($isPastDate || $isPastCutoff) {
                $staffId = $user->staff?->id;
                $hasReminder = AttendanceReminder::hasUnused($staffId, $subjectId, $date, $class, $year, $section);

                if (!$hasReminder) {
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

        // Mark matching reminder as used (one-time exception consumed)
        if ($staff?->id) {
            AttendanceReminder::consume($staff->id, $subjectId, $date, $class, $year, $section);
        }

        return count($upsertData);
    }
}
