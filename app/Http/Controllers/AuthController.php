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
            
            // Set default active role if user has roles
            if ($user->roles->count() > 0 && !session('active_role')) {
                session(['active_role' => $user->roles->first()->name]);
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
