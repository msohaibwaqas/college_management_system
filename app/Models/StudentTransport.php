<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTransport extends Model
{
    protected $table = 'student_transport';

    protected $fillable = [
        'student_id',
        'route_id',
        'stop_id',
        'vehicle_id',
        'driver_id',
        'pickup_point',
        'monthly_fee',
        'status',
        'joined_at',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
