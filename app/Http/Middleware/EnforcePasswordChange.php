<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforcePasswordChange
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            $allowedRoutes = [
                'password.force_change',
                'password.force_change.update',
                'logout',
            ];

            if (!in_array($request->route()?->getName(), $allowedRoutes)) {
                return redirect()->route('password.force_change')
                    ->with('warning', 'Your password was reset by an administrator. You must change your password before accessing the system.');
            }
        }

        return $next($request);
    }
}
