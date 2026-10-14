<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $fillable = [
        'title',
        'date',
        'status',
    ];

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }
}
