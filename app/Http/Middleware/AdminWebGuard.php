<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Server-side gate for /<admin>/* web routes.
 *
 * Primary path: read `admin_session_user_id` from the Laravel session
 * (populated by AuthController::login on successful admin login).
 *
 * Fallback path: when the session has been GC'd (SESSION_LIFETIME=120 min
 * by default) but the JWT cookie `session_token` is still valid (15 days),
 * authenticate via JWT and rebuild the session in-place so a returning
 * admin does not get 404 just because they were idle for two hours.
 *
 * In both paths we re-verify the role on every request so a demoted /
 * banned admin can be locked out without waiting for the session to expire.
 *
 * If neither path admits the request, abort(404) — indistinguishable from
 * a non-existent path so an attacker cannot enumerate the admin panel.
 */
class AdminWebGuard
{
    public function handle(Request $request, Closure $next)
    {
        $userId = (int) $request->session()->get('admin_session_user_id', 0);

        if ($userId <= 0) {
            $userId = $this->reauthenticateViaJwt($request);
        }

        if ($userId <= 0) {
            abort(404);
        }

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

    /**
     * If the JWT cookie is valid and points at an admin, repopulate the
     * session and return the user id. Otherwise return 0.
     */
    private function reauthenticateViaJwt(Request $request): int
    {
        $token = $request->cookie('session_token');
        if (!$token) {
            return 0;
        }

        try {
            $user = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
        } catch (\Throwable $e) {
            return 0;
        }

        if (!$user || empty($user->id)) {
            return 0;
        }

        $userId = (int) $user->id;
        try {
            $request->session()->put('admin_session_user_id', $userId);
        } catch (\Throwable $e) {
            // session driver glitch — let the role check below decide.
        }

        return $userId;
    }
}
