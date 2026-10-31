<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user exists in our database with this email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // User exists, log them in
                Auth::login($user);

                // Update last login
                if (method_exists($user, 'updateLastLogin')) {
                    $user->updateLastLogin();
                }

                // Set default active role if user has roles and session doesn't have it
                if ($user->roles->count() > 0 && !session('active_role')) {
                    $roleNames = $user->roles->pluck('name');
                    if ($user->is_super_admin || $roleNames->contains('super_admin')) {
                        $defaultRole = 'super_admin';
                    } elseif ($roleNames->contains('admin')) {
                        $defaultRole = 'admin';
                    } elseif ($roleNames->contains('principal')) {
                        $defaultRole = 'principal';
                    } elseif ($roleNames->contains('hod')) {
                        $defaultRole = 'hod';
                    } elseif ($roleNames->contains('teacher')) {
                        $defaultRole = 'teacher';
                    } else {
                        $defaultRole = $roleNames->first();
                    }
                    session(['active_role' => $defaultRole]);
                }

                return redirect()->intended(route('dashboard'));
            } else {
                // User does not exist in our database
                return redirect()->route('login')->withErrors([
                    'username' => 'Your Google account (' . $googleUser->getEmail() . ') is not registered in our system. Please contact the administrator.',
                ]);
            }

        } catch (Exception $e) {
            return redirect()->route('login')->withErrors([
                'username' => 'Google authentication failed. Please try again or use your username/password.',
            ]);
        }
    }
}
