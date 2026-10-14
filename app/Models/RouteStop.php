<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    protected $fillable = ['route_id', 'stop_name', 'pickup_time', 'order'];

    public function route() { return $this->belongsTo(TransportRoute::class); }
}
