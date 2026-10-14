<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Update extends Model
{
    protected $fillable = [
        'type',
        'title',
        'date',
        'image',
        'uploaded_by',
        'category',
        'status',
    ];

    public function photos()
    {
        return $this->hasMany(UpdatePhoto::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the URL for the update's image
     */
    public function getImageUrl(): string
    {
        if ($this->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->image)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image);
        }
        
        // Return placeholder if no image
        return asset('uploads/default.jpg');
    }
}
