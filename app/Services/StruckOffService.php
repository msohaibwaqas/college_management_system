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
}
