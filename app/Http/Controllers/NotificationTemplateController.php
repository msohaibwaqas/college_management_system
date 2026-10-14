<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        $templates = NotificationTemplate::orderBy('type')->get();
        return view('admin.communication.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.communication.templates.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|unique:notification_templates,type',
            'channel' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'placeholders' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        // Convert string comma-separated placeholders to array if needed (optional since cast is array, but let's keep it simple)
        if (!empty($validated['placeholders']) && is_string($validated['placeholders'])) {
            $validated['placeholders'] = array_map('trim', explode(',', $validated['placeholders']));
        }

        $validated['created_by'] = auth()->id();
        
        NotificationTemplate::create($validated);

        return redirect()->route('notification-templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(NotificationTemplate $notificationTemplate)
    {
        return view('admin.communication.templates.form', ['template' => $notificationTemplate]);
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate)
    {
        $validated = $request->validate([
            'type' => 'required|string|unique:notification_templates,type,' . $notificationTemplate->id,
            'channel' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'placeholders' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if (!empty($validated['placeholders']) && is_string($validated['placeholders'])) {
            $validated['placeholders'] = array_map('trim', explode(',', $validated['placeholders']));
        }

        $validated['updated_by'] = auth()->id();
        $validated['is_active'] = $request->has('is_active');

        $notificationTemplate->update($validated);

        return redirect()->route('notification-templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(NotificationTemplate $notificationTemplate)
    {
        $notificationTemplate->forceDelete();
        return redirect()->route('notification-templates.index')->with('success', 'Template physically deleted from database table.');
    }
}
