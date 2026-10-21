<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class FineRule extends Model
{
    protected $table = 'fine_rules';

    protected $fillable = [
        'name',
        'fine_type',
        'exam_name',
        'amount_per_unit',
        'pass_percentage',
        'academic_year',
        'class_level',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount_per_unit' => 'decimal:2',
        'pass_percentage' => 'decimal:2',
        'is_active'       => 'boolean',
        'academic_year'   => 'integer',
    ];

    public function studentFines(): HasMany
    {
        return $this->hasMany(StudentFine::class, 'fine_rule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where(function ($q) use ($year) {
            $q->whereNull('academic_year')->orWhere('academic_year', $year);
        });
    }

    public function getFormattedTypeAttribute(): string
    {
        return match ($this->fine_type) {
            'lecture_absent' => 'Lecture Absence',
            'test_absent'    => 'Test Absence',
            'test_fail'      => 'Test Failure',
            default          => ucfirst(str_replace('_', ' ', $this->fine_type)),
        };
    }
}
