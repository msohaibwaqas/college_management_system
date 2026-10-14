<?php

namespace App\Http\Controllers;

use App\Models\BannerSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BannerSlideController extends Controller
{
    /**
     * Upload a new banner slide.
     */
    public function store(Request $request)
    {
        $request->validate([
            'banner_slide'    => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'slide_title'     => 'nullable|string|max:150',
            'slide_subtitle'  => 'nullable|string|max:200',
        ]);

        // Ensure directory exists
        $destinationPath = public_path('uploads/banners');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $file     = $request->file('banner_slide');
        $filename = 'slide_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($destinationPath, $filename);

        $maxOrder = BannerSlide::max('sort_order') ?? 0;

        BannerSlide::create([
            'image_path'  => 'uploads/banners/' . $filename,
            'title'       => $request->slide_title,
            'subtitle'    => $request->slide_subtitle,
            'sort_order'  => $maxOrder + 1,
            'is_active'   => true,
        ]);

        Cache::forget('institute_settings');

        return redirect()->route('settings.edit')
            ->with('success', 'Banner slide added successfully!');
    }

    /**
     * Toggle a slide's active status.
     */
    public function toggleActive(BannerSlide $bannerSlide)
    {
        $bannerSlide->update(['is_active' => !$bannerSlide->is_active]);
        Cache::forget('institute_settings');

        return redirect()->route('settings.edit')
            ->with('success', 'Slide status updated.');
    }

    /**
     * Delete a banner slide.
     */
    public function destroy(BannerSlide $bannerSlide)
    {
        // Remove the file from disk
        $filePath = public_path($bannerSlide->image_path);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $bannerSlide->delete();
        Cache::forget('institute_settings');

        return redirect()->route('settings.edit')
            ->with('success', 'Banner slide deleted.');
    }

    /**
     * Reorder slides via AJAX (expects JSON body: { order: [id1, id2, ...] })
     */
    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array']);

        foreach ($request->order as $index => $id) {
            BannerSlide::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        Cache::forget('institute_settings');

        return response()->json(['success' => true]);
    }
}
