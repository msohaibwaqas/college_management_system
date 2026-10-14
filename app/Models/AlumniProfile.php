<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlumniProfile extends Model
{
    protected $fillable = ['student_id', 'current_city', 'employer', 'email', 'mobile'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
