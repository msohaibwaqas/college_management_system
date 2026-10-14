<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InstituteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_title',
        'institute_subtitle',
        'institute_location',
        'short_name',
        'full_name',
        'email',
        'reg_number',
        'ems_code',
        'admission_type',
        'landline',
        'contact_number',
        'whatsapp_number',
        'inquiry_email',
        'inquiry_password',
        'twitter_url',
        'facebook_url',
        'instagram_url',
        'motto',
        'address',
        'google_map_url',
        'logo_path',
        'banner_image',
        'our_history',
        'our_mission',
        'our_vision',
        'color_primary',
        'color_secondary',
        'color_highlight',
        'color_success',
        'bank_name',
        'bank_account_number',
        'bank_branch',
        'admission_start_date',
        'admission_end_date',
        'admission_fee_amount',
        'current_admission_year',
    ];

    protected $casts = [
        'admission_start_date' => 'date',
        'admission_end_date' => 'date',
        'admission_fee_amount' => 'decimal:2',
        'current_admission_year' => 'integer',
    ];

    protected $hidden = [
        'inquiry_password',
    ];

    /**
     * Get the first (and only) settings record
     */
    public static function getSettings()
    {
        return \Illuminate\Support\Facades\Cache::remember('institute_settings', 1800, function () {
            return self::first() ?? new self();
        });
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute()
    {
        if (!$this->logo_path) return null;

        // 1. Direct upload path (uploads/logos/...)
        if (strpos($this->logo_path, 'uploads/') === 0) {
            return asset($this->logo_path);
        }

        // 2. Check if it exists in public/uploads/logos/ even if DB has old path
        // e.g. DB: logos/logo.png -> Check: public/uploads/logos/logo.png
        $filename = basename($this->logo_path);
        if (file_exists(public_path('uploads/logos/' . $filename))) {
            return asset('uploads/logos/' . $filename);
        }

        // 3. Fallback to storage
        if (\Storage::disk('public')->exists($this->logo_path)) {
            return asset('storage/' . $this->logo_path);
        }
        
        return null;
    }

    /**
     * Get banner URL
     */
    public function getBannerUrlAttribute()
    {
        if (!$this->banner_image) {
            return 'https://images.unsplash.com/photo-1562774053-701939374585?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80';
        }

        // 1. Direct upload path
        if (strpos($this->banner_image, 'uploads/') === 0) {
            return asset($this->banner_image);
        }

        // 2. Check if it exists in public/uploads/banners/ even if DB has old path
        $filename = basename($this->banner_image);
        if (file_exists(public_path('uploads/banners/' . $filename))) {
            return asset('uploads/banners/' . $filename);
        }

        // 3. Fallback to storage
        if (\Storage::disk('public')->exists($this->banner_image)) {
            return asset('storage/' . $this->banner_image);
        }
        
        return 'https://images.unsplash.com/photo-1562774053-701939374585?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80';
    }

    /**
     * Check if logo exists
     */
    public function hasLogo()
    {
        if (!$this->logo_path) return false;
        
        // Always return true if we have a path, let the browser try to load it
        // This avoids server-side permission issues blocking the image
        return true; 
    }

    /**
     * Check if banner exists
     */
    public function hasBanner()
    {
        if (!$this->banner_image) return false;
        return true;
    }

    /**
     * Get color settings with defaults
     */
    public static function getColors()
    {
        $settings = self::first();
        return [
            'primary' => $settings->color_primary ?? '#006159',
            'secondary' => $settings->color_secondary ?? '#2e9f82',
            'highlight' => $settings->color_highlight ?? '#d6ec8c',
            'success' => $settings->color_success ?? '#68c170',
        ];
    }
}
