<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class UserMiddleware
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role_id == 0) { // Assuming "admin" role has ID 1
            return $next($request);
        }

        return response()->json(['ok' => false, 'message' => 'Access Denied'], 403);
    }
}
