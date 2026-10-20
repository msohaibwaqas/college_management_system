<?php

namespace App\Services;

use App\DTOs\AttendanceMetric;
use App\Models\Attendance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceCalculatorService
{
    /**
     * Calculate metric for a specific student in a subject and date range.
     */
    public function calculateForStudent(
        int $studentId,
        int $subjectId,
        ?string $startDate = null,
        ?string $endDate = null,
        float $thresholdPercent = 75.0
    ): AttendanceMetric {
        $query = Attendance::where('student_id', $studentId)
            ->where('subject_id', $subjectId);

        if ($startDate && $endDate) {
            $query->whereBetween('attendance_date', [$startDate, $endDate]);
        }

        $records = $query->get();

        $present = $records->whereIn('status', ['present', 'late'])->count();
        $absent  = $records->where('status', 'absent')->count();
        $leave   = $records->where('status', 'leave')->count();
        $total   = $records->count();

        return AttendanceMetric::fromCounts(
            delivered: $total,
            present: $present,
            absent: $absent,
            leave: $leave,
            thresholdPercent: $thresholdPercent
        );
    }

    /**
     * Compute multi-subject attendance matrix for students across enrolled subjects.
     *
     * @param Collection $studentIds
     * @param Collection $subjectIds
     * @param string $startDate
     * @param string $endDate
     * @return array Matrix in the format [$studentId => [$subjectId => AttendanceMetric]]
     */
    public function buildAttendanceMatrix(
        Collection $studentIds,
        Collection $subjectIds,
        string $startDate,
        string $endDate,
        float $thresholdPercent = 75.0
    ): array {
        // Total delivered per subject in this date range
        $deliveredPerSubject = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->select('subject_id', DB::raw('COUNT(DISTINCT attendance_date) as total_delivered'))
            ->groupBy('subject_id')
            ->pluck('total_delivered', 'subject_id');

        // Attended counts grouped by student and subject
        $attendedCounts = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->whereIn('status', ['present', 'late'])
            ->select('student_id', 'subject_id', DB::raw('COUNT(*) as attended_count'))
            ->groupBy('student_id', 'subject_id')
            ->get()
            ->keyBy(fn($item) => $item->student_id . '_' . $item->subject_id);

        // Absent counts grouped by student and subject
        $absentCounts = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereDate('attendance_date', '>=', $startDate)
            ->whereDate('attendance_date', '<=', $endDate)
            ->where('status', 'absent')
            ->select('student_id', 'subject_id', DB::raw('COUNT(*) as absent_count'))
            ->groupBy('student_id', 'subject_id')
            ->get()
            ->keyBy(fn($item) => $item->student_id . '_' . $item->subject_id);

        $matrix = [];
        foreach ($studentIds as $sId) {
            $matrix[$sId] = [];
            foreach ($subjectIds as $subId) {
                $delivered = (int) $deliveredPerSubject->get($subId, 0);
                $key = $sId . '_' . $subId;
                $attended = isset($attendedCounts[$key]) ? (int) $attendedCounts[$key]->attended_count : 0;
                $absent = isset($absentCounts[$key]) ? (int) $absentCounts[$key]->absent_count : 0;

                $matrix[$sId][$subId] = AttendanceMetric::fromCounts(
                    delivered: $delivered,
                    present: $attended,
                    absent: $absent,
                    leave: 0,
                    thresholdPercent: $thresholdPercent
                );
            }
        }

        return [
            'matrix'              => $matrix,
            'deliveredPerSubject' => $deliveredPerSubject,
        ];
    }
}
