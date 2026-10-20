<?php

namespace App\Actions\Attendance;

use App\Exceptions\AttendanceRestrictedException;
use App\Models\HeAttendance;
use App\Models\User;

class MarkHeAttendanceAction
{
    /**
     * Check if marking BS attendance is restricted for the given user and date.
     * Returns null if allowed, or an error message string if restricted.
     */
    public function checkRestrictions(User $user, string $date): ?string
    {
        // Administrative staff bypass restrictions
        if ($user->isAdministrativeStaff()) {
            return null;
        }

        if ($user->isTeacher()) {
            $today = date('Y-m-d');
            if ($date !== $today) {
                return 'Teachers can only mark attendance for the current date.';
            }
            if ((int) date('N', strtotime($date)) === 7) {
                return 'Attendance cannot be marked on Sundays.';
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

        $upsertData = [];
        foreach ($attendance as $heAdmissionId => $status) {
            $upsertData[] = [
                'he_admission_id' => (int) $heAdmissionId,
                'bs_subject_id'   => $subjectId,
                'staff_id'        => $staffId,
                'attendance_date' => $date,
                'status'          => $status,
                'remarks'         => $remarks[$heAdmissionId] ?? null,
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
