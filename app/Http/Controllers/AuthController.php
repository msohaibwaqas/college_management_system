<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Generate a simple math captcha
        $n1 = rand(1, 10);
        $n2 = rand(1, 10);
        session(['login_captcha_answer' => $n1 + $n2]);
        $captcha_question = "$n1 + $n2";
        
        return view('auth.login', compact('captcha_question'));
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|integer',
        ]);

        if ($request->captcha != session('login_captcha_answer')) {
            return back()->withErrors([
                'captcha' => 'Incorrect CAPTCHA answer.',
            ])->withInput($request->only('username'));
        }

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        // Check if user exists and is active
        $user = User::where('username', $request->username)->first();
        
        if (!$user) {
            return back()->withErrors([
                'username' => 'These credentials do not match our records.',
            ])->withInput($request->only('username'));
        }

        if ($user->status !== 'active') {
            return back()->withErrors([
                'username' => 'Your account is not active. Please contact the administrator.',
            ])->withInput($request->only('username'));
        }

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            
            // Update last login
            $user->updateLastLogin();
            
            // Set default active role on login:
            // When a user logs in and has a role of teacher, log them in as teacher
            if ($user->roles->count() > 0) {
                $roleNames = $user->roles->pluck('name');
                if ($roleNames->contains('teacher')) {
                    $defaultRole = 'teacher';
                } elseif ($user->is_super_admin || $roleNames->contains('super_admin')) {
                    $defaultRole = 'super_admin';
                } elseif ($roleNames->contains('admin')) {
                    $defaultRole = 'admin';
                } elseif ($roleNames->contains('principal')) {
                    $defaultRole = 'principal';
                } elseif ($roleNames->contains('hod')) {
                    $defaultRole = 'hod';
                } elseif ($roleNames->contains('timetable_incharge')) {
                    $defaultRole = 'timetable_incharge';
                } else {
                    $defaultRole = $roleNames->first();
                }
                session(['active_role' => $defaultRole]);
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'username' => 'These credentials do not match our records.',
        ])->withInput($request->only('username'));
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
