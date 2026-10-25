<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceReminder;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications and attendance reminders.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = $request->query('filter', 'all');

        $pendingReminders = collect();
        $usedReminders = collect();

        if ($user && $user->staff) {
            $pendingReminders = AttendanceReminder::with(['subject', 'creator'])
                ->where('staff_id', $user->staff->id)
                ->where('is_used', false)
                ->orderByDesc('created_at')
                ->get();

            $usedReminders = AttendanceReminder::with(['subject', 'creator'])
                ->where('staff_id', $user->staff->id)
                ->where('is_used', true)
                ->orderByDesc('used_at')
                ->take(15)
                ->get();
        }

        $notificationsQuery = $user->notifications();
        if ($filter === 'unread') {
            $notificationsQuery = $user->unreadNotifications();
        }

        $notifications = $notificationsQuery->paginate(20)->withQueryString();

        $unreadNotificationsCount = $user->unreadNotifications->count();
        $totalUnreadCount = $unreadNotificationsCount + $pendingReminders->count();

        return view('notifications.index', compact(
            'notifications',
            'pendingReminders',
            'usedReminders',
            'filter',
            'unreadNotificationsCount',
            'totalUnreadCount'
        ));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a notification physically from the database table.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        // 1. Delete from standard notifications table physically
        DB::table('notifications')
            ->where('id', $id)
            ->where(function ($q) use ($user) {
                if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->is_super_admin) {
                    return;
                }
                $q->where('notifiable_id', $user->id)
                  ->where('notifiable_type', get_class($user));
            })
            ->delete();

        // 2. Also delete corresponding notification log if present
        DB::table('notification_logs')->where('id', $id)->delete();

        return back()->with('success', 'Notification entry deleted.');
    }

    /**
     * Delete all notifications physically from the database table.
     */
    public function destroyAll()
    {
        $user = Auth::user();

        if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->is_super_admin) {
            DB::table('notifications')->delete();
            DB::table('notification_logs')->delete();
        } else {
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->delete();
        }

        return back()->with('success', 'All notifications cleared.');
    }
}
