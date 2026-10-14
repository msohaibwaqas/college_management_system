<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BsSubject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'theory_credit_hours',
        'practical_credit_hours',
        'type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    public function studentSubjects(): HasMany
    {
        return $this->hasMany(HeStudentSubject::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(HeTeacherAssignment::class);
    }
}
