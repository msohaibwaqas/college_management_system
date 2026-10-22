<?php

namespace App\Http\Controllers;

use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Services\Communication\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    public function index()
    {
        $announcements = Announcement::with('creator')->latest()->paginate(20);
        return view('admin.communication.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.communication.announcements.create');
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $this->announcementService->createAnnouncement(
            $request->validated(),
            Auth::id()
        );

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement composed successfully and is being processed in the background.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->announcementService->deleteAnnouncement($announcement);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement and recipient entries physically deleted from database table.');
    }
}
