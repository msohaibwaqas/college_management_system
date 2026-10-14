<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GraduationBatch extends Model
{
    protected $fillable = ['name', 'year', 'is_graduated'];

    public function records()
    {
        return $this->hasMany(GraduationRecord::class, 'batch_id');
    }
}
