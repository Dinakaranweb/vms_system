<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tender;

class CheckTenderAccess
{
    public function handle(Request $request, Closure $next)
    {
        $tenderId = $request->route('id') ?? $request->route('tenderId');
        
        if ($tenderId && !auth()->user()->canAccessTender($tenderId)) {
            abort(403, 'You do not have permission to access this tender.');
        }
        
        return $next($request);
    }
}