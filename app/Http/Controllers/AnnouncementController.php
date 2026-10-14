<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator')->latest()->paginate(20);
        return view('admin.communication.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.communication.announcements.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'recipient_group' => 'required|array', // e.g. ['students', 'teaching_staff', 'non_teaching_staff']
            'channels' => 'required|array',        // e.g. ['email', 'sms', 'in-app']
            'is_holiday' => 'nullable|boolean',
            'holiday_date' => 'required_if:is_holiday,1|nullable|date',
            'scheduled_at' => 'nullable|date|after_or_equal:now'
        ]);

        $isHoliday = $request->has('is_holiday') && $request->boolean('is_holiday');

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'recipient_group' => implode(',', $validated['recipient_group']),
            'is_holiday' => $isHoliday,
            'holiday_date' => $isHoliday ? $validated['holiday_date'] : null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'created_by' => auth()->id()
        ]);

        // Dispatch background job to process sending
        dispatch(new \App\Jobs\ProcessBulkAnnouncement($announcement, $validated['channels']));

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement composed successfully and is being processed in the background.');
    }

    public function destroy(Announcement $announcement)
    {
        // Delete recipients and force delete announcement physically from database table
        $announcement->recipients()->delete();
        $announcement->forceDelete();

        return redirect()->route('announcements.index')->with('success', 'Announcement and recipient entries physically deleted from database table.');
    }
}
