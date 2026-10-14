<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class StaffPromotionHistory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'staff_promotion_histories';

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
        'promotion_number',
        'previous_designation',
        'new_designation',
        'previous_bps',
        'new_bps',
        'promotion_date',
        'promotion_order_number',
        'promotion_order_date',
        'promotion_order_file',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'promotion_number' => 'integer',
        'previous_bps' => 'integer',
        'new_bps' => 'integer',
        'promotion_date' => 'date',
        'promotion_order_date' => 'date',
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

    public function scopeForPromotionNumber(Builder $query, int $number): Builder
    {
        return $query->where('promotion_number', $number);
    }

    public function scopeForBpsRange(Builder $query, int $minBps, int $maxBps): Builder
    {
        return $query->whereBetween('new_bps', [$minBps, $maxBps]);
    }

    public function scopeAfterDate(Builder $query, string $date): Builder
    {
        return $query->where('promotion_date', '>=', $date);
    }

    public function scopeBeforeDate(Builder $query, string $date): Builder
    {
        return $query->where('promotion_date', '<=', $date);
    }

    public function scopeSearchByDesignation(Builder $query, string $designation): Builder
    {
        return $query->where(function($q) use ($designation) {
            $q->where('previous_designation', 'like', "%{$designation}%")
              ->orWhere('new_designation', 'like', "%{$designation}%");
        });
    }

    // Accessors
    public function getBpsIncreaseAttribute(): int
    {
        return $this->new_bps - $this->previous_bps;
    }

    public function getIsPromotionAttribute(): bool
    {
        return $this->new_bps > $this->previous_bps;
    }

    public function getIsDemotionAttribute(): bool
    {
        return $this->new_bps < $this->previous_bps;
    }

    public function getIsSameGradeAttribute(): bool
    {
        return $this->new_bps == $this->previous_bps;
    }

    public function getYearsSincePromotionAttribute(): float
    {
        if (!$this->promotion_date) {
            return 0;
        }
        return $this->promotion_date->diffInDays(now()) / 365.25;
    }

    public function getPromotionOrderFileUrlAttribute(): string
    {
        if ($this->promotion_order_file) {
            return asset('storage/' . $this->promotion_order_file);
        }
        return '';
    }

    public function getFormattedPromotionNumberAttribute(): string
    {
        $suffixes = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        $number = $this->promotion_number;
        $suffix = $suffixes[min($number, 9)];
        
        if ($number % 100 >= 11 && $number % 100 <= 13) {
            $suffix = 'th';
        }
        
        return $number . $suffix;
    }

    // Mutators
    public function setPreviousDesignationAttribute($value): void
    {
        $this->attributes['previous_designation'] = trim(ucwords(strtolower($value)));
    }

    public function setNewDesignationAttribute($value): void
    {
        $this->attributes['new_designation'] = trim(ucwords(strtolower($value)));
    }

    public function setPromotionOrderNumberAttribute($value): void
    {
        $this->attributes['promotion_order_number'] = $value ? trim($value) : null;
    }

    // Custom methods
    public function getPromotionSummary(): array
    {
        return [
            'promotion_number' => $this->promotion_number,
            'formatted_promotion_number' => $this->formatted_promotion_number,
            'previous_designation' => $this->previous_designation,
            'new_designation' => $this->new_designation,
            'previous_bps' => $this->previous_bps,
            'new_bps' => $this->new_bps,
            'bps_increase' => $this->bps_increase,
            'promotion_date' => $this->promotion_date,
            'promotion_order_number' => $this->promotion_order_number,
            'is_promotion' => $this->is_promotion,
            'is_demotion' => $this->is_demotion,
            'years_since_promotion' => $this->years_since_promotion,
        ];
    }

    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'promotion_summary' => $this->getPromotionSummary(),
            'promotion_order_date' => $this->promotion_order_date,
            'promotion_order_file' => $this->promotion_order_file,
            'promotion_order_file_url' => $this->promotion_order_file_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isValidPromotion(): bool
    {
        return $this->is_promotion && $this->bps_increase > 0;
    }

    public function getPromotionType(): string
    {
        if ($this->bps_increase > 2) {
            return 'Major Promotion';
        } elseif ($this->bps_increase == 2) {
            return 'Regular Promotion';
        } elseif ($this->bps_increase == 1) {
            return 'Minor Promotion';
        } elseif ($this->bps_increase == 0) {
            return 'Grade Change';
        } else {
            return 'Demotion';
        }
    }

    public function isEligibleForNextPromotion(): bool
    {
        // Example: Check if 3+ years have passed since last promotion
        return $this->years_since_promotion >= 3;
    }

    public function getYearsToNextPromotion(): int
    {
        $requiredYears = 3; // Assuming 3 years between promotions
        $yearsSince = $this->years_since_promotion;
        
        return $yearsSince >= $requiredYears ? 0 : (int)($requiredYears - $yearsSince);
    }
}
