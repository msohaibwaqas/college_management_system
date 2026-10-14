<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = ['model_name', 'registration_no', 'chassis_no', 'engine_no', 'total_seats', 'status'];

    public function routes()
    {
        return $this->hasMany(TransportRoute::class, 'vehicle_id');
    }
}
