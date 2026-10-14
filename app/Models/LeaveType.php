<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'default_days', 'max_leaves_per_month', 'special_case_max_leaves_per_month',
        'period_start_date', 'period_end_date', 'is_paid', 'requires_document', 
        'is_active', 'description', 'created_by', 'updated_by', 'deleted_by'
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_document' => 'boolean',
        'is_active' => 'boolean',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'max_leaves_per_month' => 'integer',
        'special_case_max_leaves_per_month' => 'integer',
    ];
}
