<?php

namespace App\Http\Controllers;

use App\Models\AdmissionSetting;
use App\Models\InstituteSetting;
use Illuminate\Http\Request;

class AdmissionSettingsController extends Controller
{
    /**
     * Show the admission settings page
     */
    public function index()
    {
        $interSettings = AdmissionSetting::getForProgram('Inter');
        $adpSettings = AdmissionSetting::getForProgram('ADP');
        $bsSettings = AdmissionSetting::getForProgram('BS');

        return view('admission-applications.settings', compact('interSettings', 'adpSettings', 'bsSettings'));
    }

    /**
     * Update settings for a specific program
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'program_type' => 'required|string|in:Inter,ADP,BS,DIT',
            'is_open' => 'nullable|string', // Checkboxes send 'on' if checked, null otherwise
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'admission_year' => 'required|integer|digits:4',
        ]);

        $settings = AdmissionSetting::updateOrCreate(
            ['program_type' => $validated['program_type']],
            [
                'is_open' => $request->has('is_open'),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'admission_year' => $validated['admission_year'],
            ]
        );

        return redirect()->route('admissions.settings.index')
            ->with('success', "Settings for {$validated['program_type']} updated successfully!");
    }

    /**
     * Migrate existing inter settings from InstituteSetting
     */
    public function migrateExisting()
    {
        $inst = InstituteSetting::first();
        if ($inst) {
            AdmissionSetting::updateOrCreate(
                ['program_type' => 'Inter'],
                [
                    'start_date' => $inst->admission_start_date,
                    'end_date' => $inst->admission_end_date,
                    'admission_year' => $inst->current_admission_year,
                    'is_open' => ($inst->admission_start_date && $inst->admission_end_date && 
                                 now()->between($inst->admission_start_date, $inst->admission_end_date))
                ]
            );
            return redirect()->route('admissions.settings.index')
                ->with('success', 'Existing settings migrated successfully!');
        }
        return redirect()->route('admissions.settings.index')
            ->with('warning', 'No existing settings found to migrate.');
    }
}
