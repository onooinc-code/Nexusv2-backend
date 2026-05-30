<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to check if user has dashboard access
 * You can customize this based on your authorization logic
 */
class DashboardAccess
{
    public function handle(Request $request, Closure $next)
    {
        // Option 1: Check if user is authenticated
        // if (!auth()->check()) {
        //     return redirect('/login');
        // }

        // Option 2: Check if user has admin role
        // if (!auth()->user()->isAdmin()) {
        //     return redirect('/');
        // }

        // Option 3: Check using gates/policies
        // $this->authorize('view-dashboard');

        return $next($request);
    }
}
