<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * IsSuperAdmin Middleware
 *
 * Ensures the authenticated user is a super-admin.
 * Returns 403 Forbidden if user is not super-admin or not authenticated.
 */
class IsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'Authentication required'
            ], 401);
        }

        // Check if user is super-admin
        if (!($request->user()->is_super_admin ?? false)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'Super-admin access required'
            ], 403);
        }

        return $next($request);
    }
}
