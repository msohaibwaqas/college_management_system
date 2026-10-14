<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HsscRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'bise_id',
        'roll_no',
        'reg_no',
        'college_name',
        'total_mark',
        'obtained_mark',
        'passing_year',
        'passing_session',
        'hssc_group',
        'created_by',
        'updated_by',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function bise(): BelongsTo
    {
        return $this->belongsTo(BiseRecord::class, 'bise_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
