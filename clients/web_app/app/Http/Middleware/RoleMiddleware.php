<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $allowedRoles = array_map('intval', $roles);

        if (!in_array((int) session('role_id'), $allowedRoles, true)) {
            abort(403, 'Akses ditolak');
        }

        return $next($request);
    }
}
