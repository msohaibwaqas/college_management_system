<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PublicHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'date', 'type', 'description', 'is_active'
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];
}
