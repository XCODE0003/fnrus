<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class Access
{

    public function handle($request, Closure $next, $role) {
        try {
            switch ($role) {
                case 'admin':
                    if ($request->user()->role_id < 3) {
                        if ($request->ajax())
                            return response()->setStatusCode(403);
                        abort(404);
                    }
                    break;
                case 'moderator':
                    if ($request->user()->chat_role < 2) {
                        if ($request->ajax())
                            return response()->setStatusCode(403);
                        abort(404);
                    }
                    break;
                default:
                    return response()->setStatusCode(403);
                    break;
            }
            return $next($request);
        } catch(\Exception $e) {
            return response()->setStatusCode(403);
        }
    }

}
