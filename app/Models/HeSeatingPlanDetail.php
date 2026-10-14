<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeSeatingPlanDetail extends Model
{
    use HasFactory;

    protected $table = 'he_seating_plan_details';

    protected $fillable = [
        'he_seating_plan_id',
        'he_admission_id',
        'room_id',
        'seat_number',
        'row_number',
    ];

    protected $casts = [
        'seat_number' => 'integer',
        'row_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function seatingPlan(): BelongsTo
    {
        return $this->belongsTo(HeSeatingPlan::class, 'he_seating_plan_id');
    }

    public function heAdmission(): BelongsTo
    {
        return $this->belongsTo(HeAdmission::class, 'he_admission_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    // Accessors
    public function getStudentNameAttribute(): string
    {
        if ($this->heAdmission && $this->heAdmission->student) {
            $student = $this->heAdmission->student;
            return trim($student->first_name . ' ' . ($student->last_name ?? ''));
        }
        return 'Unknown Student';
    }

    public function getRollNoAttribute(): string
    {
        return $this->heAdmission ? $this->heAdmission->roll_no : 'N/A';
    }

    public function getRoomNameAttribute(): string
    {
        return $this->room ? $this->room->name : 'N/A';
    }
}
