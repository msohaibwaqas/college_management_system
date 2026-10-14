<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Datesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_date',
        'admission_year',
        'subject_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'test_date' => 'date',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
