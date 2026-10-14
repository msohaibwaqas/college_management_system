<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_id', 'recipient_type', 'channel', 'type', 'message', 
        'status', 'attempts', 'sent_at', 'failed_reason'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function recipient()
    {
        return $this->morphTo();
    }
}
