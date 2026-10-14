<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->student_id) {
            // Students can only chat with their assigned teachers who have accounts
            $teachers = \App\Models\Staff::has('user')->whereIn('id', function($query) use ($user) {
                $query->select('staff_id')->from('student_teacher_subjects')->where('student_id', $user->student_id);
            })->with('user')->get();

            return view('lms.chat.index', compact('teachers'));
        }

        if ($user->staff_id || $user->hasAnyRole(['admin', 'principal'])) {
            // Teachers see students assigned to them, Admins see all students
            $query = \App\Models\Student::has('user')->with(['user', 'admissions', 'heAdmissions']);
            
            if ($user->staff_id) {
                // If the user is a teacher (even if admin), show their assigned students first
                $query->whereIn('id', function($q) use ($user) {
                    $q->select('student_id')->from('student_teacher_subjects')->where('staff_id', $user->staff_id);
                });
            } elseif ($user->hasAnyRole(['admin', 'principal'])) {
                // Admins see all students but let's increase the limit to 500 or paginate
                $query->latest();
            } else {
                return back()->with('error', 'Unauthorized access.');
            }

            $students = $query->with(['user' => function($q) use ($user) {
                $q->withCount(['sentMessages as unread_count' => function($q) use ($user) {
                    $q->where('receiver_id', $user->id)->whereNull('read_at');
                }]);
            }])->get();
            return view('lms.chat.teacher_index', compact('students'));
        }

        return back()->with('error', 'Unauthorized access.');
    }

    public function show(\App\Models\User $recipient)
    {
        $user = auth()->user();
        
        $messages = \App\Models\ChatMessage::where(function($q) use ($user, $recipient) {
            $q->where('sender_id', $user->id)->where('receiver_id', $recipient->id);
        })->orWhere(function($q) use ($user, $recipient) {
            $q->where('sender_id', $recipient->id)->where('receiver_id', $user->id);
        })->oldest()->get();

        // Mark as read
        \App\Models\ChatMessage::where('sender_id', $recipient->id)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('lms.chat.show', compact('recipient', 'messages'));
    }

    public function send(Request $request, \App\Models\User $recipient)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        \App\Models\ChatMessage::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $recipient->id,
            'message' => $request->message,
        ]);

        return back()->with('success', 'Message sent.');
    }
}
