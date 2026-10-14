<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;

class GateAttendanceArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year',
        'total_days_present',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
