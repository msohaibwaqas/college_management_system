<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdatePhoto extends Model
{
    protected $fillable = [
        'update_id',
        'path',
    ];

    public function update()
    {
        return $this->belongsTo(Update::class);
    }
}
