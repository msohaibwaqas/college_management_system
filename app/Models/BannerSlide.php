<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BannerSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_path',
        'title',
        'subtitle',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope to only active slides ordered by sort_order
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the full public URL of the banner image.
     */
    public function getImageUrlAttribute(): string
    {
        if (!$this->image_path) {
            return 'https://images.unsplash.com/photo-1562774053-701939374585?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80';
        }

        // Direct public path (uploads/banners/...)
        if (str_starts_with($this->image_path, 'uploads/')) {
            return asset($this->image_path);
        }

        // Check if file exists in public uploads/banners/
        $filename = basename($this->image_path);
        if (file_exists(public_path('uploads/banners/' . $filename))) {
            return asset('uploads/banners/' . $filename);
        }

        // Fallback to storage
        if (\Storage::disk('public')->exists($this->image_path)) {
            return asset('storage/' . $this->image_path);
        }

        return 'https://images.unsplash.com/photo-1562774053-701939374585?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80';
    }

    /**
     * Get all active slides, falling back to the single banner_image from InstituteSetting
     * if no slides have been configured yet.
     */
    public static function getActiveSlides()
    {
        $slides = self::active()->get();

        if ($slides->isEmpty()) {
            // Build a virtual "slide" from the legacy single banner image
            $settings = InstituteSetting::getSettings();
            $virtual = new self([
                'image_path' => $settings->banner_image ?? '',
                'title'      => null,
                'subtitle'   => null,
            ]);
            return collect([$virtual]);
        }

        return $slides;
    }
}
