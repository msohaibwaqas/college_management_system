<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $fillable = ['name', 'bps'];

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }
}
