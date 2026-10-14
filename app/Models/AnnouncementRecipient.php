<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnnouncementRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id', 'recipient_id', 'recipient_type', 'channel', 'status'
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }

    public function recipient()
    {
        return $this->morphTo();
    }
}
