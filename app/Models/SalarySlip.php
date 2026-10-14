<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySlip extends Model
{
    protected $fillable = [
        'payroll_run_id', 'staff_id', 'base_salary', 'total_earnings', 
        'total_deductions', 'absence_deduction', 'net_salary', 
        'components_breakdown', 'status', 'payment_method', 'transaction_id'
    ];

    protected $casts = [
        'components_breakdown' => 'array',
    ];

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
