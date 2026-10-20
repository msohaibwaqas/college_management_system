<?php

namespace App\DTOs;

class AttendanceMetric
{
    public function __construct(
        public readonly int $totalDelivered,
        public readonly int $presentCount,
        public readonly int $absentCount,
        public readonly int $leaveCount,
        public readonly float $percentage,
        public readonly bool $isShortAttendance = false,
        public readonly ?string $statusBadgeClass = null
    ) {}

    /**
     * Create metric from raw counts.
     */
    public static function fromCounts(
        int $delivered,
        int $present,
        int $absent,
        int $leave = 0,
        float $thresholdPercent = 75.0
    ): self {
        $effectiveTotal = $delivered > 0 ? $delivered : ($present + $absent + $leave);
        $percentage = $effectiveTotal > 0 ? round(($present / $effectiveTotal) * 100, 1) : 0.0;
        $isShort = $percentage < $thresholdPercent;

        $badgeClass = match (true) {
            $percentage >= 85 => 'success',
            $percentage >= $thresholdPercent => 'info',
            $percentage >= 50 => 'warning',
            default => 'danger',
        };

        return new self(
            totalDelivered: $effectiveTotal,
            presentCount: $present,
            absentCount: $absent,
            leaveCount: $leave,
            percentage: $percentage,
            isShortAttendance: $isShort,
            statusBadgeClass: $badgeClass
        );
    }

    public function toArray(): array
    {
        return [
            'total_delivered'     => $this->totalDelivered,
            'present_count'       => $this->presentCount,
            'absent_count'        => $this->absentCount,
            'leave_count'         => $this->leaveCount,
            'percentage'          => $this->percentage,
            'is_short_attendance' => $this->isShortAttendance,
            'badge_class'         => $this->statusBadgeClass,
        ];
    }
}
