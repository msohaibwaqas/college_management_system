<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $fillable = [
        'slot_number',
        'name',
        'start_time',
        'end_time'
    ];

    public function entries()
    {
        return $this->hasMany(TimetableEntry::class);
    }
}
