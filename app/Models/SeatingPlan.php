<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatingPlan extends Model
{
    protected $fillable = ['test_date', 'created_by', 'updated_by'];

    protected $casts = [
        'test_date' => 'date',
    ];

    public function details()
    {
        return $this->hasMany(SeatingPlanDetail::class);
    }

    public function invigilatorAssignments()
    {
        return $this->hasMany(\App\Models\InvigilatorAssignment::class);
    }
}
