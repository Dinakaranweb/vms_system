<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }
        
        $user = Auth::user();
        
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }
        
        // For tender-owner trying to access admin routes
        if (in_array('admin', $roles) && $user->hasRole('tender-owner')) {
            abort(403, 'Unauthorized access. Tender owners cannot access admin area.');
        }
        
        abort(403, 'Unauthorized access. You do not have permission to access this page.');
    }
}