<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeSeatingPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'he_seating_plans';

    protected $fillable = [
        'he_datesheet_id',
        'title',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function datesheet(): BelongsTo
    {
        return $this->belongsTo(HeDatesheet::class, 'he_datesheet_id')->withTrashed();
    }

    public function details(): HasMany
    {
        return $this->hasMany(HeSeatingPlanDetail::class, 'he_seating_plan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getStudentCountAttribute(): int
    {
        return $this->details()->count();
    }

    public function getRoomCountAttribute(): int
    {
        return $this->details()->distinct('room_id')->count('room_id');
    }
}
