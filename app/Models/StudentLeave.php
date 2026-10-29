<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class StudentLeave extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_leaves';

    protected $fillable = [
        'student_id',
        'start_date',
        'end_date',
        'days_count',
        'leave_type',
        'reason',
        'approved_by',
        'marked_by',
        'document_path',
        'status',
        'remarks',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'days_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    // Scopes
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : (string) $date;
        return $query->where('status', 'approved')
            ->whereDate('start_date', '<=', $dateStr)
            ->whereDate('end_date', '>=', $dateStr);
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeOverlapping(Builder $query, int $studentId, string $startDate, string $endDate, ?int $excludeId = null): Builder
    {
        return $query->where('student_id', $studentId)
            ->where('status', 'approved')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereDate('start_date', '<=', $endDate)
                  ->whereDate('end_date', '>=', $startDate);
            });
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);
        return $query->where(function ($q) use ($search) {
            $q->where('reason', 'like', "%{$search}%")
              ->orWhere('approved_by', 'like', "%{$search}%")
              ->orWhere('remarks', 'like', "%{$search}%")
              ->orWhereHas('student', function ($sq) use ($search) {
                  $sq->search($search);
              });
        });
    }

    // Helpers
    public function isActiveOn($date): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }
        $target = Carbon::parse($date)->startOfDay();
        return $target->betweenIncluded($this->start_date->startOfDay(), $this->end_date->startOfDay());
    }

    public function getFormattedDateRangeAttribute(): string
    {
        if (!$this->start_date || !$this->end_date) {
            return 'N/A';
        }
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('d M Y') . ' (1 day)';
        }
        return $this->start_date->format('d M Y') . ' — ' . $this->end_date->format('d M Y') . ' (' . $this->days_count . ' days)';
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return match ($this->leave_type) {
            'casual'          => 'Casual Leave',
            'medical'         => 'Medical / Sick Leave',
            'family_visit'    => 'Family / Relative Visit',
            'urgent_affair'   => 'Urgent Personal Affair',
            'official'        => 'Official / College Event',
            default           => ucfirst(str_replace('_', ' ', $this->leave_type ?? 'Other')),
        };
    }

    public function getDocumentUrlAttribute(): ?string
    {
        if (!$this->document_path) {
            return null;
        }
        return asset($this->document_path);
    }
}
