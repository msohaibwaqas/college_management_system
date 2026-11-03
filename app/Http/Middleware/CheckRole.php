<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check for active role from session (Role Switching)
        $activeRole = session('active_role');
        if ($activeRole) {
            if (in_array($activeRole, $roles)) {
                return $next($request);
            }
            // An HOD is inherently a teacher with teaching responsibilities
            if ($activeRole === 'hod' && in_array('teacher', $roles)) {
                return $next($request);
            }
            // A teacher who is an HOD can access HOD routes
            if ($activeRole === 'teacher' && in_array('hod', $roles) && $user->hasRole('hod')) {
                return $next($request);
            }
            // A teacher who is also timetable_incharge can access timetable_incharge routes
            if ($activeRole === 'teacher' && in_array('timetable_incharge', $roles) && $user->hasRole('timetable_incharge')) {
                return $next($request);
            }
            // An HOD who is also timetable_incharge can access timetable_incharge routes
            if ($activeRole === 'hod' && in_array('timetable_incharge', $roles) && $user->hasRole('timetable_incharge')) {
                return $next($request);
            }
            // A timetable_incharge who is also a teacher can access teacher routes
            if ($activeRole === 'timetable_incharge' && in_array('teacher', $roles) && $user->hasRole('teacher')) {
                return $next($request);
            }
            // A timetable_incharge who is also an HOD can access HOD routes
            if ($activeRole === 'timetable_incharge' && in_array('hod', $roles) && $user->hasRole('hod')) {
                return $next($request);
            }
            // If active role is set but not allowed, deny access.
            // This strictly enforces the switched role's permissions.
            abort(403, 'Unauthorized access. You do not have the required role.');
        }

        // Super admin can access everything (only if no active role set)
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Check if user has any of the required roles
        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access. You do not have the required role.');
    }
}
