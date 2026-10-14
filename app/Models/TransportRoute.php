<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    protected $fillable = ['name', 'start_point', 'end_point', 'vehicle_id', 'driver_id', 'monthly_fee'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function stops() { return $this->hasMany(RouteStop::class, 'route_id')->orderBy('order'); }
    public function assignments() { return $this->hasMany(StudentTransport::class, 'route_id'); }
}
