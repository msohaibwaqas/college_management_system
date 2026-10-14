<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class StaffServiceHistory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'staff_service_histories';

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
        'institution_name',
        'designation',
        'bps',
        'start_date',
        'end_date',
        'is_current',
        'reason_for_leaving',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'bps' => 'integer',
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

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopePrevious(Builder $query): Builder
    {
        return $query->where('is_current', false);
    }

    public function scopeForInstitution(Builder $query, string $institution): Builder
    {
        return $query->where('institution_name', 'like', "%{$institution}%");
    }

    public function scopeForDesignation(Builder $query, string $designation): Builder
    {
        return $query->where('designation', 'like', "%{$designation}%");
    }

    public function scopeForBps(Builder $query, int $bps): Builder
    {
        return $query->where('bps', $bps);
    }

    public function scopeActiveDuring(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhere(function($q) use ($startDate, $endDate) {
                  $q->where('start_date', '<=', $startDate)
                    ->where(function($r) use ($endDate) {
                        $r->where('end_date', '>=', $endDate)
                          ->orWhereNull('end_date');
                    });
              });
        });
    }

    public function scopeSearchByInstitution(Builder $query, string $search): Builder
    {
        return $query->where('institution_name', 'like', "%{$search}%");
    }

    // Accessors
    public function getIsPreviousAttribute(): bool
    {
        return !($this->attributes['is_current'] ?? false);
    }

    public function getServiceDurationInDaysAttribute(): int
    {
        if ($this->is_current) {
            return $this->start_date->diffInDays(now());
        }
        
        if ($this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }
        
        return 0;
    }

    public function getServiceDurationInYearsAttribute(): float
    {
        return $this->service_duration_in_days / 365.25;
    }

    public function getIsStillEmployedAttribute(): bool
    {
        return $this->is_current || ($this->end_date && $this->end_date->isFuture());
    }

    public function getFormattedInstitutionNameAttribute(): string
    {
        return ucwords(strtolower($this->institution_name));
    }

    public function getFormattedDesignationAttribute(): string
    {
        return ucwords(strtolower($this->designation));
    }

    public function getFullDesignationAttribute(): string
    {
        return $this->designation . ' (BPS-' . $this->bps . ')';
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->start_date && ($this->end_date || $this->is_current);
    }

    // Mutators
    public function setInstitutionNameAttribute($value): void
    {
        $this->attributes['institution_name'] = trim(ucwords(strtolower($value)));
    }

    public function setDesignationAttribute($value): void
    {
        $this->attributes['designation'] = trim(ucwords(strtolower($value)));
    }

    public function setReasonForLeavingAttribute($value): void
    {
        $this->attributes['reason_for_leaving'] = $value ? trim(ucwords(strtolower($value))) : null;
    }

    // Custom methods
    public function hasValidDuration(): bool
    {
        return $this->is_complete;
    }

    public function getDurationSummary(): array
    {
        return [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_current' => $this->is_current,
            'duration_days' => $this->service_duration_in_days,
            'duration_years' => $this->service_duration_in_years,
            'is_complete' => $this->is_complete,
        ];
    }

    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'institution_name' => $this->formatted_institution_name,
            'designation' => $this->formatted_designation,
            'full_designation' => $this->full_designation,
            'bps' => $this->bps,
            'duration_summary' => $this->getDurationSummary(),
            'reason_for_leaving' => $this->reason_for_leaving,
            'is_still_employed' => $this->is_still_employed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getEmploymentType(): string
    {
        if ($this->is_current) {
            return 'Current Employment';
        } elseif ($this->end_date && $this->end_date->isFuture()) {
            return 'Future Employment';
        } elseif ($this->end_date && $this->end_date->isPast()) {
            return 'Previous Employment';
        }
        return 'Unknown';
    }

    public function getCareerProgression(): array
    {
        $history = $this->staff->serviceHistory()
                              ->orderBy('start_date')
                              ->get();
        
        $progression = [];
        foreach ($history as $record) {
            $progression[] = [
                'institution' => $record->institution_name,
                'designation' => $record->designation,
                'bps' => $record->bps,
                'start_date' => $record->start_date,
                'end_date' => $record->end_date,
                'duration_years' => $record->service_duration_in_years,
            ];
        }
        
        return $progression;
    }

    public function getCareerSummary(): array
    {
        $totalYears = $this->staff->serviceHistory()
                                 ->sum('service_duration_in_years');
        
        $highestBps = $this->staff->serviceHistory()
                                 ->max('bps');
        
        $institutions = $this->staff->serviceHistory()
                                   ->pluck('institution_name')
                                   ->unique()
                                   ->toArray();
        
        return [
            'total_service_years' => round($totalYears, 2),
            'highest_bps_achieved' => $highestBps,
            'institutions_served' => $institutions,
            'total_institutions' => count($institutions),
        ];
    }

    public function isStillEmployed(): bool
    {
        return $this->is_still_employed;
    }

    public function isActive(): bool
    {
        return $this->is_current;
    }
}
