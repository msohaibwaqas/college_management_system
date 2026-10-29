<?php

namespace App\Actions\Attendance;

use App\Exceptions\AttendanceRestrictedException;
use App\Models\HeAttendance;
use App\Models\User;
use App\Services\AcademicCalendarService;

class MarkHeAttendanceAction
{
    public function __construct(
        protected AcademicCalendarService $academicCalendarService
    ) {}

    /**
     * Check if marking BS attendance is restricted for the given user and date.
     * Returns null if allowed, or an error message string if restricted.
     */
    public function checkRestrictions(User $user, string $date): ?string
    {
        // Strict Vacation / Holiday / Sunday lock: No staff member can mark attendance during this period
        [$isOff, $reason] = $this->academicCalendarService->checkIfSundayOrHoliday($date);
        if ($isOff) {
            return "Attendance cannot be marked on {$date} because it is a {$reason}.";
        }

        // Administrative staff bypass cutoff restrictions
        if ($user->isAdministrativeStaff()) {
            return null;
        }

        if ($user->isTeacher()) {
            $today = date('Y-m-d');
            if ($date !== $today) {
                return 'Teachers can only mark attendance for the current date.';
            }
            if ((int) date('H') >= 23) {
                return 'Attendance marking is closed after 11:00 PM.';
            }
        }

        return null;
    }

    /**
     * Execute BS attendance marking via bulk upsert.
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

        $restrictionError = $this->checkRestrictions($user, $date);
        if ($restrictionError !== null) {
            throw new AttendanceRestrictedException($restrictionError);
        }

        $staff = $user->staff;
        $staffId = $staff?->id ?? $user->id;
        $attendance = $data['attendance'] ?? [];
        $remarks = $data['remarks'] ?? [];

        // Check active approved leaves for these students on this date
        $heAdmissions = \App\Models\HeAdmission::whereIn('id', array_keys($attendance))->get(['id', 'student_id'])->keyBy('id');
        $studentIds = $heAdmissions->pluck('student_id')->filter()->unique();
        $activeLeaves = \App\Models\StudentLeave::approved()->forDate($date)->whereIn('student_id', $studentIds)->get()->keyBy('student_id');

        $upsertData = [];
        foreach ($attendance as $heAdmissionId => $status) {
            $studentRemarks = $remarks[$heAdmissionId] ?? null;
            $sId = $heAdmissions[$heAdmissionId]->student_id ?? null;

            if ($sId && isset($activeLeaves[$sId])) {
                $leave = $activeLeaves[$sId];
                if ($status === 'absent') {
                    $status = 'leave';
                    $studentRemarks = $studentRemarks ?: "Approved Leave: {$leave->reason}";
                } elseif ($status === 'leave' && empty($studentRemarks)) {
                    $studentRemarks = "Approved Leave: {$leave->reason}";
                }
            }

            $upsertData[] = [
                'he_admission_id' => (int) $heAdmissionId,
                'bs_subject_id'   => $subjectId,
                'staff_id'        => $staffId,
                'attendance_date' => $date,
                'status'          => $status,
                'remarks'         => $studentRemarks,
            ];
        }

        if (!empty($upsertData)) {
            HeAttendance::upsert(
                $upsertData,
                ['he_admission_id', 'bs_subject_id', 'attendance_date'],
                ['staff_id', 'status', 'remarks']
            );
        }

        return count($upsertData);
    }
}
