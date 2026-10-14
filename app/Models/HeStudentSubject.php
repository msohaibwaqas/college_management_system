<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeStudentSubject extends Model
{
    protected $fillable = [
        'he_admission_id',
        'bs_subject_id',
        'semester_no',
        'status',
        'created_by',
        'updated_by',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(HeAdmission::class, 'he_admission_id');
    }

    public function heAdmission(): BelongsTo
    {
        return $this->admission();
    }

    public function bsSubject(): BelongsTo
    {
        return $this->belongsTo(BsSubject::class, 'bs_subject_id');
    }
}
