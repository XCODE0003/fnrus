<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Replacement for Filament\Http\Middleware\Authenticate that bridges the
 * SPA's JWT auth into Filament's web-guard session.
 *
 * Filament uses the `web` guard (cookie session) but the rest of the
 * site authenticates via JWT — stored both in the JWT auth guard and as
 * a `session_token` cookie set by public/assets/js/main.js after
 * /api/auth/login. Without this bridge, an admin already logged in on
 * the site would still hit Filament's login UI / get redirected to
 * /api/login.
 *
 * Resolution order (mirrors AdminWebGuard so behaviour is identical):
 *   1. Auth::guard('web')->check() — already authenticated this session.
 *   2. Session key `admin_session_user_id` (set by AuthController::login
 *      for admin-role users).
 *   3. JWT in cookie `session_token` (set by the SPA, 15-day lifetime).
 *
 * If any path resolves to an active admin, log them into the web guard
 * and let Filament's parent class handle the canAccessPanel() role
 * check. If nothing resolves, abort(404) — indistinguishable from a
 * non-existent path so the admin panel cannot be enumerated.
 */
class FilamentSiteAuthBridge extends FilamentAuthenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        if (Auth::guard('web')->check()) {
            parent::authenticate($request, $guards);
            return;
        }

        $userId = (int) $request->session()->get('admin_session_user_id', 0);

        if ($userId <= 0) {
            $userId = $this->resolveFromJwtCookie($request);
        }

        if ($userId <= 0) {
            abort(404);
        }

        $minRole = (int) config('admin.min_role_id', 1);
        $row = DB::table('users')
            ->select('id', 'role_id', 'is_ban', 'is_active')
            ->where('id', $userId)
            ->first();

        if (! $row
            || (int) $row->is_ban === 1
            || (int) ($row->is_active ?? 1) !== 1
            || (int) $row->role_id < $minRole) {
            $request->session()->forget('admin_session_user_id');
            abort(404);
        }

        Auth::guard('web')->loginUsingId($userId);
        $request->session()->put('admin_session_user_id', $userId);

        parent::authenticate($request, $guards);
    }

    private function resolveFromJwtCookie(Request $request): int
    {
        $token = $request->cookie('session_token');
        if (! $token) {
            return 0;
        }

        try {
            $user = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
        } catch (\Throwable $e) {
            return 0;
        }

        return (int) ($user->id ?? 0);
    }
}
