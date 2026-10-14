<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotificationSetting;

class NotificationSettingsController extends Controller
{
    public function index()
    {
        $settings = NotificationSetting::pluck('value', 'key')->toArray();
        return view('admin.settings.notifications', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            NotificationSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'updated_by' => auth()->id()]
            );
        }

        return redirect()->back()->with('success', 'Notification settings updated successfully.');
    }
}
