<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type', 'channel', 'subject', 'body', 'placeholders', 'is_active',
        'created_by', 'updated_by', 'deleted_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'placeholders' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
