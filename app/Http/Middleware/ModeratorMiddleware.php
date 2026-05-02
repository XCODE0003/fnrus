<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ModeratorMiddleware
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role_id == 2) { // Assuming "moderator" role has ID 1
            return $next($request);
        }

        abort(403, 'Access denied');
    }
}
