<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'body', 'recipient_group', 'is_holiday', 'holiday_date', 'scheduled_at', 'sent_at', 
        'created_by', 'updated_by', 'deleted_by'
    ];

    protected $casts = [
        'is_holiday' => 'boolean',
        'holiday_date' => 'date',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }
}
