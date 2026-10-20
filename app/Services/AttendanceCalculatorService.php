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

    /**
     * Enrich a collection of students with full_name, recent status indicators, badge styles, and row highlights.
     *
     * @param Collection $students
     * @param int $subjectId
     * @param string $currentDate
     * @return Collection
     */
    public function enrichStudentsWithRecentAttendance(Collection $students, int $subjectId, string $currentDate): Collection
    {
        $studentIds = $students->pluck('id');
        $recentAttendances = Attendance::whereIn('student_id', $studentIds)
            ->where('subject_id', $subjectId)
            ->where('attendance_date', '<', $currentDate)
            ->orderBy('attendance_date', 'desc')
            ->get()
            ->groupBy('student_id');

        foreach ($students as $student) {
            $student->full_name = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

            $history = $recentAttendances->get($student->id, collect());
            $consecutiveAbsents = 0;
            $lastStatus = null;

            if ($history->isNotEmpty()) {
                $lastRecord = $history->first();
                $lastStatus = strtolower($lastRecord->status);

                if ($lastStatus === 'absent') {
                    foreach ($history as $record) {
                        if (strtolower($record->status) === 'absent') {
                            $consecutiveAbsents++;
                        } else {
                            break;
                        }
                    }
                }
            }

            // Assign status label, badge style, and row highlight style based on recent attendance
            if ($lastStatus === 'present' || $lastStatus === 'late') {
                $student->recent_status_code = 'green';
                $student->recent_status_label = 'Present Last Class';
                $student->recent_badge_style = 'background-color: #198754 !important; color: #ffffff !important;';
                $student->row_bg_style = 'background-color: rgba(25, 135, 84, 0.08);';
            } elseif ($consecutiveAbsents === 1) {
                $student->recent_status_code = 'yellow';
                $student->recent_status_label = '1 Day Absent';
                $student->recent_badge_style = 'background-color: #ffc107 !important; color: #212529 !important;';
                $student->row_bg_style = 'background-color: rgba(255, 193, 7, 0.15);';
            } elseif ($consecutiveAbsents === 2) {
                $student->recent_status_code = 'orange';
                $student->recent_status_label = '2 Consecutive Absents';
                $student->recent_badge_style = 'background-color: #fd7e14 !important; color: #ffffff !important;';
                $student->row_bg_style = 'background-color: rgba(253, 126, 20, 0.15);';
            } elseif ($consecutiveAbsents === 3) {
                $student->recent_status_code = 'blue';
                $student->recent_status_label = '3 Consecutive Absents';
                $student->recent_badge_style = 'background-color: #0d6efd !important; color: #ffffff !important;';
                $student->row_bg_style = 'background-color: rgba(13, 110, 253, 0.15);';
            } elseif ($consecutiveAbsents >= 4) {
                $student->recent_status_code = 'red';
                $student->recent_status_label = "{$consecutiveAbsents} Consecutive Absents";
                $student->recent_badge_style = 'background-color: #dc3545 !important; color: #ffffff !important;';
                $student->row_bg_style = 'background-color: rgba(220, 53, 69, 0.18);';
            } elseif ($lastStatus === 'leave') {
                $student->recent_status_code = 'leave';
                $student->recent_status_label = 'On Leave Last Class';
                $student->recent_badge_style = 'background-color: #0dcaf0 !important; color: #000000 !important;';
                $student->row_bg_style = 'background-color: rgba(13, 202, 240, 0.1);';
            } else {
                $student->recent_status_code = 'none';
                $student->recent_status_label = 'No Record';
                $student->recent_badge_style = 'background-color: #6c757d !important; color: #ffffff !important;';
                $student->row_bg_style = '';
            }
        }

        return $students;
    }
}
