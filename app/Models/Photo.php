<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $fillable = [
        'album_id',
        'path',
        'title',
        'date',
        'status',
    ];


    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Get the URL for the photo
     */
    public function getImageUrl(): string
    {
        if ($this->path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->path);
        }
        
        // Return placeholder if image doesn't exist
        return asset('uploads/default.jpg');
    }
}
