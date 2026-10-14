<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    protected $fillable = ['payroll_month', 'generated_by', 'status', 'total_amount', 'notes'];

    protected $casts = [
        'payroll_month' => 'date',
    ];

    public function slips()
    {
        return $this->hasMany(SalarySlip::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'generated_by');
    }
}
