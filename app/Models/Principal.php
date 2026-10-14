<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Principal extends Model
{
    protected $fillable = [
        'title',
        'name',
        'gender',
        'father_name',
        'designation',
        'from_date',
        'to_date',
        'details',
        'image',
        'is_current',
        'staff_id',
        'status',
    ];


    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the URL for the principal's image
     */
    public function getImageUrl(): string
    {
        if ($this->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->image)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image);
        }
        
        // Return default placeholder
        return asset('uploads/default.jpg');
    }
    /**
     * Get an excerpt of the principal's message
     */
    public function getExcerpt(int $limit = 300): string
    {
        if (!$this->details) {
            return '';
        }
        
        return \Illuminate\Support\Str::limit(strip_tags($this->details), $limit);
    }
}
