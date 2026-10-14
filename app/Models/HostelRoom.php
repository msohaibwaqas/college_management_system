<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelRoom extends Model
{
    protected $fillable = ['hostel_id', 'room_number', 'floor', 'type', 'capacity', 'monthly_rent', 'status'];

    public function hostel() { return $this->belongsTo(Hostel::class); }
    public function allotments() { return $this->hasMany(HostelAllotment::class, 'room_id'); }
}
