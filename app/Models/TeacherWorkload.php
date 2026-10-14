<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;



class TeacherWorkload extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'teacher_workloads';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'staff_id',
        'academic_year',
        'total_assigned_subjects',
        'total_assigned_students',
        'max_allowed_students',
        'workload_status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'academic_year' => 'integer',
        'total_assigned_subjects' => 'integer',
        'total_assigned_students' => 'integer',
        'max_allowed_students' => 'integer',
        'workload_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Relationships
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    // Scopes
    public function scopeForStaff(Builder $query, int $staffId): Builder
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeForAcademicYear(Builder $query, int $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForWorkloadStatus(Builder $query, string $status): Builder
    {
        return $query->where('workload_status', $status);
    }

    public function scopeUnderloaded(Builder $query): Builder
    {
        return $query->where('workload_status', 'underloaded');
    }

    public function scopeBalanced(Builder $query): Builder
    {
        return $query->where('workload_status', 'balanced');
    }

    public function scopeOverloaded(Builder $query): Builder
    {
        return $query->where('workload_status', 'overloaded');
    }

    public function scopeWithHighWorkload(Builder $query, int $threshold = 80): Builder
    {
        return $query->whereRaw('(total_assigned_students / max_allowed_students * 100) >= ?', [$threshold]);
    }

    public function scopeWithLowWorkload(Builder $query, int $threshold = 50): Builder
    {
        return $query->whereRaw('(total_assigned_students / max_allowed_students * 100) <= ?', [$threshold]);
    }

    public function scopeSearchByStaffName(Builder $query, string $name): Builder
    {
        return $query->join('staff', 'teacher_workloads.staff_id', '=', 'staff.id')
                    ->where(function($q) use ($name) {
                        $q->where('staff.first_name', 'like', "%{$name}%")
                          ->orWhere('staff.last_name', 'like', "%{$name}%");
                    })
                    ->select('teacher_workloads.*');
    }

    // Accessors
    public function getStaffNameAttribute(): string
    {
        return $this->staff ? $this->staff->full_name : 'Unknown Staff';
    }

    public function getStaffDesignationAttribute(): string
    {
        return $this->staff ? $this->staff->designation : 'Unknown';
    }

    public function getWorkloadPercentageAttribute(): float
    {
        if ($this->max_allowed_students == 0) {
            return 0.0;
        }
        return round(($this->total_assigned_students / $this->max_allowed_students) * 100, 2);
    }

    public function getIsUnderloadedAttribute(): bool
    {
        return $this->workload_status === 'underloaded';
    }

    public function getIsBalancedAttribute(): bool
    {
        return $this->workload_status === 'balanced';
    }

    public function getIsOverloadedAttribute(): bool
    {
        return $this->workload_status === 'overloaded';
    }

    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->max_allowed_students - $this->total_assigned_students);
    }

    public function getLoadRatioAttribute(): float
    {
        if ($this->total_assigned_subjects == 0) {
            return 0.0;
        }
        return round($this->total_assigned_students / $this->total_assigned_subjects, 2);
    }

    public function getWorkloadCategoryAttribute(): string
    {
        $percentage = $this->workload_percentage;
        
        if ($percentage < 60) return 'Light';
        if ($percentage < 85) return 'Moderate';
        if ($percentage < 100) return 'Heavy';
        return 'Overloaded';
    }

    public function getIsWithinCapacityAttribute(): bool
    {
        return $this->total_assigned_students <= $this->max_allowed_students;
    }

    public function getIsExceedingCapacityAttribute(): bool
    {
        return $this->total_assigned_students > $this->max_allowed_students;
    }

    // Mutators
    public function setWorkloadStatusAttribute($value): void
    {
        $this->attributes['workload_status'] = $value ?: $this->calculateWorkloadStatus();
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'staff_name' => $this->staff_name,
            'staff_designation' => $this->staff_designation,
            'academic_year' => $this->academic_year,
            'total_assigned_subjects' => $this->total_assigned_subjects,
            'total_assigned_students' => $this->total_assigned_students,
            'max_allowed_students' => $this->max_allowed_students,
            'workload_percentage' => $this->workload_percentage,
            'workload_status' => $this->workload_status,
            'workload_category' => $this->workload_category,
            'available_capacity' => $this->available_capacity,
            'load_ratio' => $this->load_ratio,
            'is_underloaded' => $this->is_underloaded,
            'is_balanced' => $this->is_balanced,
            'is_overloaded' => $this->is_overloaded,
            'is_within_capacity' => $this->is_within_capacity,
            'is_exceeding_capacity' => $this->is_exceeding_capacity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getWorkloadSummary(): array
    {
        return [
            'total_subjects' => $this->total_assigned_subjects,
            'total_students' => $this->total_assigned_students,
            'max_allowed' => $this->max_allowed_students,
            'percentage' => $this->workload_percentage,
            'status' => $this->workload_status,
            'category' => $this->workload_category,
            'available_capacity' => $this->available_capacity,
        ];
    }

    public function getLoadAnalysis(): array
    {
        return [
            'workload_percentage' => $this->workload_percentage,
            'status' => $this->workload_status,
            'category' => $this->workload_category,
            'capacity_utilization' => $this->workload_percentage . '%',
            'recommendation' => $this->getWorkloadRecommendation(),
        ];
    }

    public function getWorkloadRecommendation(): string
    {
        $percentage = $this->workload_percentage;
        
        if ($percentage < 50) {
            return 'Consider increasing workload';
        } elseif ($percentage < 85) {
            return 'Maintain current workload';
        } elseif ($percentage < 100) {
            return 'Monitor workload closely';
        } else {
            return 'Reduce workload immediately';
        }
    }

    public function calculateWorkloadStatus(): string
    {
        $percentage = $this->workload_percentage;
        
        if ($percentage < 60) {
            return 'underloaded';
        } elseif ($percentage <= 100) {
            return 'balanced';
        } else {
            return 'overloaded';
        }
    }

    public function updateWorkloadStatus(): void
    {
        $newStatus = $this->calculateWorkloadStatus();
        if ($this->workload_status !== $newStatus) {
            $this->update(['workload_status' => $newStatus]);
        }
    }

    public function getPerformanceIndicators(): array
    {
        return [
            'students_per_subject' => $this->load_ratio,
            'workload_percentage' => $this->workload_percentage,
            'capacity_utilization' => $this->workload_category,
            'status' => $this->workload_status,
        ];
    }

    public function getWorkloadTrend(): string
    {
        $previousWorkload = $this->staff->teacherWorkloads()
                                       ->where('academic_year', $this->academic_year - 1)
                                       ->first();
        
        if (!$previousWorkload) {
            return 'New Teacher';
        }
        
        $current = $this->workload_percentage;
        $previous = $previousWorkload->workload_percentage;
        
        if ($current > $previous + 10) {
            return 'Increased';
        } elseif ($current < $previous - 10) {
            return 'Decreased';
        } else {
            return 'Stable';
        }
    }

    public function canAcceptMoreStudents(): bool
    {
        return $this->available_capacity > 0;
    }

    public function canAcceptMoreSubjects(): bool
    {
        return $this->workload_percentage < 90; // Assuming 90% is the limit
    }

    public function getCapacityAlert(): string
    {
        $percentage = $this->workload_percentage;
        
        if ($percentage >= 100) {
            return 'Critical - Over Capacity';
        } elseif ($percentage >= 90) {
            return 'Warning - Near Capacity';
        } elseif ($percentage >= 75) {
            return 'Caution - High Load';
        } else {
            return 'Normal - Within Capacity';
        }
    }
}
