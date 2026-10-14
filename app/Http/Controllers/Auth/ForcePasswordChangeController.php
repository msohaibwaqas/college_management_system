<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ForcePasswordChangeController extends Controller
{
    /**
     * Display forced password change screen.
     */
    public function showForm()
    {
        $user = Auth::user();

        if (!$user->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.force_change_password', compact('user'));
    }

    /**
     * Process password update.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'Your new password cannot be the same as your temporary password.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password provided is incorrect.']);
        }

        $user->password = $request->password;
        $user->must_change_password = false;
        $user->password_changed_at = now();
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Your password has been changed successfully!');
    }
}
