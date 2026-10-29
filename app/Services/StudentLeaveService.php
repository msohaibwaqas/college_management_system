<?php

namespace App\Services;

use App\Models\StudentLeave;
use App\Models\Attendance;
use App\Models\HeAttendance;
use App\Models\Student;
use App\Models\Staff;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentLeaveService
{
    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Create and record a student leave, then automatically sync attendance.
     */
    public function createLeave(array $data, ?UploadedFile $document, int $markedByUserId): StudentLeave
    {
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $daysCount = $start->diffInDays($end) + 1;

        $documentPath = null;
        if ($document) {
            $studentId = (int) $data['student_id'];
            $documentPath = $this->fileUploadService->upload(
                $document,
                'uploads/student_leaves',
                "student_{$studentId}_leave"
            );
        }

        return DB::transaction(function () use ($data, $start, $end, $daysCount, $documentPath, $markedByUserId) {
            $leave = StudentLeave::create([
                'student_id'    => (int) $data['student_id'],
                'start_date'    => $start->toDateString(),
                'end_date'      => $end->toDateString(),
                'days_count'    => $daysCount,
                'leave_type'    => $data['leave_type'] ?? 'casual',
                'reason'        => $data['reason'],
                'approved_by'   => $data['approved_by'] ?? 'Principal Office',
                'marked_by'     => $markedByUserId,
                'document_path' => $documentPath,
                'status'        => 'approved',
                'remarks'       => $data['remarks'] ?? null,
            ]);

            $this->syncAttendanceForLeave($leave);

            return $leave;
        });
    }

    /**
     * Update an existing student leave and re-sync attendance.
     */
    public function updateLeave(StudentLeave $leave, array $data, ?UploadedFile $document): StudentLeave
    {
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $daysCount = $start->diffInDays($end) + 1;

        $documentPath = $leave->document_path;
        if ($document) {
            $documentPath = $this->fileUploadService->upload(
                $document,
                'uploads/student_leaves',
                "student_{$leave->student_id}_leave",
                $leave->document_path
            );
        }

        return DB::transaction(function () use ($leave, $data, $start, $end, $daysCount, $documentPath) {
            $leave->update([
                'start_date'    => $start->toDateString(),
                'end_date'      => $end->toDateString(),
                'days_count'    => $daysCount,
                'leave_type'    => $data['leave_type'] ?? $leave->leave_type,
                'reason'        => $data['reason'],
                'approved_by'   => $data['approved_by'] ?? $leave->approved_by,
                'document_path' => $documentPath,
                'remarks'       => $data['remarks'] ?? $leave->remarks,
            ]);

            $this->syncAttendanceForLeave($leave);

            return $leave;
        });
    }

    /**
     * Cancel a student leave.
     */
    public function cancelLeave(StudentLeave $leave, ?string $cancelRemarks = null): bool
    {
        return DB::transaction(function () use ($leave, $cancelRemarks) {
            $remarks = $leave->remarks;
            if ($cancelRemarks) {
                $remarks = trim(($remarks ? $remarks . ' | ' : '') . 'Cancelled: ' . $cancelRemarks);
            }

            $leave->update([
                'status'  => 'cancelled',
                'remarks' => $remarks,
            ]);

            // Clean up or note attendance records marked for this leave
            $period = CarbonPeriod::create($leave->start_date, $leave->end_date);
            $dates = [];
            foreach ($period as $date) {
                $dates[] = $date->toDateString();
            }

            Attendance::where('student_id', $leave->student_id)
                ->whereIn('attendance_date', $dates)
                ->where('status', 'leave')
                ->where('remarks', 'like', "%Approved Leave: {$leave->reason}%")
                ->update(['remarks' => DB::raw("CONCAT(COALESCE(remarks, ''), ' (Leave Cancelled)')")]);

            return true;
        });
    }

    /**
     * Synchronize and record attendance records for the student across all enrolled subjects.
     * Converts any pre-existing 'absent' status to 'leave', and pre-records 'leave' for future dates.
     *
     * @return int Number of attendance records created or updated
     */
    public function syncAttendanceForLeave(StudentLeave $leave): int
    {
        if ($leave->status !== 'approved') {
            return 0;
        }

        $recordsCount = 0;
        $period = CarbonPeriod::create($leave->start_date, $leave->end_date);
        $reasonText = "Approved Leave: " . $leave->reason;

        // 1. HSSC (Intermediate) synchronization
        $teacherSubjects = DB::table('student_teacher_subjects')
            ->where('student_id', $leave->student_id)
            ->whereNull('deleted_at')
            ->get(['subject_id', 'staff_id']);

        // Default staff fallback in case of unassigned subject
        $defaultStaffId = Staff::first()?->id ?? 1;

        if ($teacherSubjects->isNotEmpty()) {
            foreach ($period as $dateObj) {
                $dateStr = $dateObj->toDateString();

                foreach ($teacherSubjects as $ts) {
                    $staffId = $ts->staff_id ?: $defaultStaffId;

                    $existing = Attendance::where('student_id', $leave->student_id)
                        ->where('subject_id', $ts->subject_id)
                        ->where('attendance_date', $dateStr)
                        ->first();

                    if ($existing) {
                        // If it was marked absent (or anything else), convert it to leave to save from strike off!
                        $existing->update([
                            'status'  => 'leave',
                            'remarks' => $reasonText,
                        ]);
                        $recordsCount++;
                    } else {
                        // Pre-record attendance as leave
                        Attendance::create([
                            'student_id'      => $leave->student_id,
                            'subject_id'      => $ts->subject_id,
                            'staff_id'        => $staffId,
                            'attendance_date' => $dateStr,
                            'status'          => 'leave',
                            'remarks'         => $reasonText,
                        ]);
                        $recordsCount++;
                    }
                }
            }
        }

        // 2. Higher Education (BS / ADP) synchronization (if student is in BS)
        $heAdmissions = DB::table('he_admissions')
            ->where('student_id', $leave->student_id)
            ->whereIn('status', ['Active', 'Admitted', 'active', 'admitted'])
            ->get(['id']);

        foreach ($heAdmissions as $heAdm) {
            $assignments = DB::table('he_teacher_assignments')
                ->where('he_admission_id', $heAdm->id)
                ->get(['bs_subject_id', 'staff_id']);

            if ($assignments->isNotEmpty()) {
                foreach ($period as $dateObj) {
                    $dateStr = $dateObj->toDateString();

                    foreach ($assignments as $asg) {
                        $staffId = $asg->staff_id ?: $defaultStaffId;

                        $existingHe = HeAttendance::where('he_admission_id', $heAdm->id)
                            ->where('bs_subject_id', $asg->bs_subject_id)
                            ->where('attendance_date', $dateStr)
                            ->first();

                        if ($existingHe) {
                            $existingHe->update([
                                'status'  => 'leave',
                                'remarks' => $reasonText,
                            ]);
                            $recordsCount++;
                        } else {
                            HeAttendance::create([
                                'he_admission_id' => $heAdm->id,
                                'bs_subject_id'   => $asg->bs_subject_id,
                                'staff_id'        => $staffId,
                                'attendance_date' => $dateStr,
                                'status'          => 'leave',
                                'remarks'         => $reasonText,
                            ]);
                            $recordsCount++;
                        }
                    }
                }
            }
        }

        return $recordsCount;
    }

    /**
     * Get active approved leaves for a list of student IDs on a specific date.
     * Returns a collection keyed by student_id.
     */
    public function getActiveLeavesForStudents(array $studentIds, string $date): Collection
    {
        if (empty($studentIds)) {
            return collect();
        }

        return StudentLeave::approved()
            ->whereIn('student_id', $studentIds)
            ->forDate($date)
            ->get()
            ->keyBy('student_id');
    }

    /**
     * Get summary metrics for the Student Leaves dashboard.
     */
    public function getLeaveStats(): array
    {
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $totalApproved = StudentLeave::approved()->count();

        $onLeaveToday = StudentLeave::approved()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        $leavesThisMonth = StudentLeave::approved()
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->count();

        $upcomingLeaves = StudentLeave::approved()
            ->whereDate('start_date', '>', $today)
            ->count();

        return compact('totalApproved', 'onLeaveToday', 'leavesThisMonth', 'upcomingLeaves');
    }
}
