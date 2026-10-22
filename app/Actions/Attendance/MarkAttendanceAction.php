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
        // Administrative staff can mark without restrictions
        if ($user->isAdministrativeStaff()) {
            return null;
        }

        // Regular teachers have date, day, holiday, and time restrictions
        if ($user->isTeacher()) {
            // Check if the date is a Sunday or Holiday (Public or Announced)
            [$isOff, $reason] = $this->academicCalendarService->checkIfSundayOrHoliday($date);
            if ($isOff) {
                return "Attendance cannot be marked on {$date} because it is a {$reason}.";
            }

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

        $upsertData = [];
        foreach ($attendance as $studentId => $status) {
            $upsertData[] = [
                'student_id'      => (int) $studentId,
                'subject_id'      => $subjectId,
                'staff_id'        => $staffId,
                'attendance_date' => $date,
                'status'          => $status,
                'remarks'         => $remarks[$studentId] ?? null,
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
