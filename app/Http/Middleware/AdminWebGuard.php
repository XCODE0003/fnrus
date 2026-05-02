<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Server-side gate for /<admin>/* web routes.
 *
 * Checks the `admin_session_user_id` session key (populated on successful
 * login by AuthController::login when the user has admin role). If the
 * session is missing, expired, or the user no longer has the required role,
 * we return a hard 404 — indistinguishable from a non-existent path so an
 * attacker cannot enumerate that the admin panel exists at this URL.
 *
 * The session cookie is set by Laravel's StartSession middleware (web group)
 * after the login API call; same-origin XHR / form POSTs honour the cookie
 * automatically.
 */
class AdminWebGuard
{
    public function handle(Request $request, Closure $next)
    {
        $userId = (int) $request->session()->get('admin_session_user_id', 0);
        if ($userId <= 0) {
            abort(404);
        }

        // Re-check role on every request (in case admin was demoted).
        $user = DB::table('users')
            ->select('id', 'role_id', 'is_ban')
            ->where('id', $userId)
            ->first();

        $minRole = (int) config('admin.min_role_id', 1);

        if (!$user || (int) $user->is_ban === 1 || (int) $user->role_id < $minRole) {
            $request->session()->forget('admin_session_user_id');
            abort(404);
        }

        return $next($request);
    }
}
