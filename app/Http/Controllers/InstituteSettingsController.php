<?php

namespace App\Http\Controllers;

use App\Models\InstituteSetting;
use App\Models\BannerSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InstituteSettingsController extends Controller
{
    /**
     * Show the settings form
     */
    public function edit()
    {
        $settings     = InstituteSetting::getSettings();
        $bannerSlides = BannerSlide::orderBy('sort_order')->orderBy('id')->get();
        return view('settings.edit', compact('settings', 'bannerSlides'));
    }

    /**
     * Update the settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'institute_title' => 'required|string|max:150',
            'institute_subtitle' => 'nullable|string|max:150',
            'institute_location' => 'nullable|string|max:100',
            'short_name' => 'nullable|string|max:50',
            'full_name' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:100',
            'reg_number' => 'nullable|string|max:50',
            'ems_code' => 'nullable|string|max:50',
            'admission_type' => 'nullable|string|max:100',
            'landline' => 'nullable|string|max:20',
            'contact_number' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'inquiry_email' => 'nullable|email|max:100',
            'inquiry_password' => 'nullable|string',
            'twitter_url' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'motto' => 'nullable|string',
            'address' => 'nullable|string',
            'google_map_url' => 'nullable|string',
        ]);

        $settings = InstituteSetting::first();
        
        if (!$settings) {
            $settings = InstituteSetting::create($request->except('logo'));
        } else {
            $settings->update($request->except('logo'));
        }

        Cache::forget('institute_settings');

        return redirect()->route('settings.edit')
            ->with('success', 'Settings updated successfully!');
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        try {
            $settings = InstituteSetting::first();
            if (!$settings) {
                $settings = InstituteSetting::create(['institute_title' => 'College Name']);
            }

            // Ensure directory exists in PUBLIC folder
            $destinationPath = public_path('uploads/logos');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Delete old logo if exists
            if ($settings->logo_path) {
                $oldPath = public_path($settings->logo_path);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Upload new logo
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
                
                // Move directly to public folder
                $file->move($destinationPath, $filename);
                
                // Save relative path (e.g., 'uploads/logos/logo_123.png')
                $settings->update(['logo_path' => 'uploads/logos/' . $filename]);
            }

            Cache::forget('institute_settings');

            return redirect()->route('settings.edit')
                ->with('success', 'Logo uploaded successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.edit')
                ->with('error', 'Failed to upload logo: ' . $e->getMessage());
        }
    }

    /**
     * Upload banner image
     */
    public function uploadBanner(Request $request)
    {
        $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max for banner
        ]);

        try {
            $settings = InstituteSetting::first();
            if (!$settings) {
                $settings = InstituteSetting::create(['institute_title' => 'College Name']);
            }

            // Ensure directory exists in PUBLIC folder
            $destinationPath = public_path('uploads/banners');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Delete old banner if exists
            if ($settings->banner_image) {
                $oldPath = public_path($settings->banner_image);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            // Upload new banner
            if ($request->hasFile('banner')) {
                $file = $request->file('banner');
                $filename = 'banner_' . time() . '.' . $file->getClientOriginalExtension();
                
                // Move directly to public folder
                $file->move($destinationPath, $filename);
                
                // Save relative path (e.g., 'uploads/banners/banner_123.jpg')
                $settings->update(['banner_image' => 'uploads/banners/' . $filename]);
            }

            Cache::forget('institute_settings');

            return redirect()->route('settings.edit')
                ->with('success', 'Banner image uploaded successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.edit')
                ->with('error', 'Failed to upload banner: ' . $e->getMessage());
        }
    }
}
