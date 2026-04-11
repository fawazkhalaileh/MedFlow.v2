<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Usage in routes: ->middleware('role:secretary,technician')
     * System admin (employee_type=system_admin OR role=admin) always passes.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $type = $user->employee_type ?? '';
        $role = $user->role ?? '';

        // Super admin bypasses all role gates
        if ($type === 'system_admin' || $role === 'admin') {
            return $next($request);
        }

        // Check against allowed roles
        if (in_array($type, $roles) || in_array($role, $roles)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this area. Contact your system administrator.');
    }
}
