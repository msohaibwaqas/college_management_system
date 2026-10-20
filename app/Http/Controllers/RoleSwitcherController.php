<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSwitcherController extends Controller
{
    /**
     * Switch the active role for the current user session.
     */
    public function switch(Request $request)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = Auth::user();
        $role = $request->role;

        // Verify the user actually has this role
        if (!$user->hasRole($role) && !$user->is_super_admin) {
            return back()->with('error', 'You do not have permission to switch to this role.');
        }

        // Store the active role in the session
        session(['active_role' => $role]);

        // Default sidebar view based on role
        if ($user->isBsRole()) {
            session(['sidebar_view' => 'BS']);
        } else {
            session(['sidebar_view' => 'HSSC']);
        }

        // Redirect to the dashboard (or appropriate landing page for that role)
        return redirect()->route('dashboard')->with('success', "Switched to " . ucfirst($role) . " view.");
    }

    /**
     * Switch the sidebar view (HSSC / BS) for admins.
     */
    public function switchView(Request $request)
    {
        $request->validate([
            'view' => 'required|in:HSSC,BS',
        ]);

        session(['sidebar_view' => $request->view]);

        return back()->with('success', "Switched to " . $request->view . " view.");
    }
}
