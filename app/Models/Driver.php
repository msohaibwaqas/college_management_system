<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = ['staff_id', 'name', 'cnic', 'license_no', 'license_expiry', 'mobile'];

    public function routes()
    {
        return $this->hasMany(TransportRoute::class, 'driver_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
