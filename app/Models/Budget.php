<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = ['department_name', 'fiscal_year', 'allocated_amount', 'notes'];

    public function expenditures()
    {
        return $this->hasMany(Expenditure::class);
    }

    public function getUsedAmountAttribute()
    {
        return $this->expenditures()->sum('amount');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->allocated_amount - $this->used_amount;
    }
}
