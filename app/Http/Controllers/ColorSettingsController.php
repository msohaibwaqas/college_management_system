<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\InstituteSetting;

class ColorSettingsController extends Controller
{
    /**
     * Display the color settings page
     */
    public function index()
    {
        $settings = InstituteSetting::first();
        return view('admin.settings.colors', compact('settings'));
    }

    /**
     * Update color settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'color_primary' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_secondary' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_highlight' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_success' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ], [
            'color_primary.regex' => 'Primary color must be a valid hex color code (e.g., #006159)',
            'color_secondary.regex' => 'Secondary color must be a valid hex color code (e.g., #2e9f82)',
            'color_highlight.regex' => 'Highlight color must be a valid hex color code (e.g., #d6ec8c)',
            'color_success.regex' => 'Success color must be a valid hex color code (e.g., #68c170)',
        ]);

        $settings = InstituteSetting::first();
        
        if (!$settings) {
            $settings = InstituteSetting::create($request->only([
                'color_primary',
                'color_secondary',
                'color_highlight',
                'color_success',
            ]));
        } else {
            $settings->update($request->only([
                'color_primary',
                'color_secondary',
                'color_highlight',
                'color_success',
            ]));
        }

        return redirect()->back()->with('success', 'Color settings updated successfully!');
    }

    /**
     * Reset colors to defaults
     */
    public function reset()
    {
        $settings = InstituteSetting::first();
        
        if ($settings) {
            $settings->update([
                'color_primary' => '#006159',
                'color_secondary' => '#2e9f82',
                'color_highlight' => '#d6ec8c',
                'color_success' => '#68c170',
            ]);
        }

        return redirect()->back()->with('success', 'Colors reset to defaults successfully!');
    }
}
