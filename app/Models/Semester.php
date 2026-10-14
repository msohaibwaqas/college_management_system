<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Semester extends Model
{
    protected $fillable = [
        'program_id',
        'bs_subject_id',
        'semester_no',
        'created_by',
        'updated_by',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    public function bsSubject(): BelongsTo
    {
        return $this->belongsTo(BsSubject::class, 'bs_subject_id');
    }
}
