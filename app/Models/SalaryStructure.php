<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    protected $fillable = ['name', 'base_salary', 'is_active'];

    public function components()
    {
        return $this->hasMany(SalaryComponent::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }
}
