<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class EnforceMaxSessionDuration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if last_login_at is present and more than 5 hours ago
            if ($user->last_login_at) {
                $lastLogin = Carbon::parse($user->last_login_at);
                $diffInHours = $lastLogin->diffInHours(now());

                if ($diffInHours >= 5) {
                    Auth::logout();

                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'username' => 'Your session has expired (5-hour safety limit reached). Please login again.',
                    ]);
                }
            }
        }

        return $next($request);
    }
}
