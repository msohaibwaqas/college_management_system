<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    protected $fillable = ['salary_structure_id', 'name', 'type', 'amount_type', 'amount'];

    public function salaryStructure()
    {
        return $this->belongsTo(SalaryStructure::class);
    }
}
