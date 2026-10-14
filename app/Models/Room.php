<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'class_capacity',
        'location',
        'description',
        'is_active',
        'purpose',
        'created_by',
        'updated_by',
    ];

    public function scopeForLectures($query)
    {
        return $query->whereIn('purpose', ['lecture', 'both']);
    }

    public function scopeForExams($query)
    {
        return $query->whereIn('purpose', ['exam', 'both']);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
