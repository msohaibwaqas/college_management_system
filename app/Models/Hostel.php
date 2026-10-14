<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hostel extends Model
{
    protected $fillable = ['name', 'type', 'capacity', 'warden_id', 'address'];

    public function rooms() { return $this->hasMany(HostelRoom::class); }
    public function warden() { return $this->belongsTo(Staff::class, 'warden_id'); }
}
