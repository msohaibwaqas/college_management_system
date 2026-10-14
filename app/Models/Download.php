<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    use HasFactory;

    protected $table = 'downloads';

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'published_date',
        'uploaded_by',
        'file_size',
        'download_count',
    ];

    protected $casts = [
        'published_date' => 'date',
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    /**
     * Get the user who uploaded this file
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get formatted published date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->published_date->format('M d, Y');
    }

    /**
     * Increment download counter
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get file extension
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    /**
     * Get original filename
     */
    public function getOriginalFilenameAttribute(): string
    {
        return basename($this->file_path);
    }
}
