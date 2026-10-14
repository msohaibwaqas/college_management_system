<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(20);
        
        return view('notifications.index', compact('notifications'));
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
        $deletedCount = DB::table('notifications')
            ->where('id', $id)
            ->where(function ($q) use ($user) {
                if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->is_super_admin) {
                    // Admin can delete any notification entry
                    return;
                }
                // Otherwise user can delete their own notification
                $q->where('notifiable_id', $user->id)
                  ->where('notifiable_type', get_class($user));
            })
            ->delete();

        // 2. Also delete corresponding notification log if present
        DB::table('notification_logs')->where('id', $id)->delete();

        return back()->with('success', 'Notification entry deleted from database table.');
    }

    /**
     * Delete all notifications physically from the database table.
     */
    public function destroyAll()
    {
        $user = Auth::user();

        if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->is_super_admin) {
            // Admin deletes all entries physically from database tables
            DB::table('notifications')->delete();
            DB::table('notification_logs')->delete();
        } else {
            // User deletes all their own entries physically
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', get_class($user))
                ->delete();
        }

        return back()->with('success', 'All notification entries deleted from database table.');
    }
}
