<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GraduationRecord extends Model
{
    protected $fillable = ['batch_id', 'student_id', 'graduation_date', 'status', 'remarks'];

    public function batch()
    {
        return $this->belongsTo(GraduationBatch::class, 'batch_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
