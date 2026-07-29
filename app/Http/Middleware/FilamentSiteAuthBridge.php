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
        $debug = filter_var(env('FILAMENT_AUTH_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
        $log = function (string $step, array $ctx = []) use ($debug, $request): void {
            if (! $debug) {
                return;
            }
            \Log::channel('single')->info('filament_bridge: ' . $step, $ctx + [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'has_session_token_cookie' => $request->cookie('session_token') !== null,
                'has_admin_session' => (int) $request->session()->get('admin_session_user_id', 0) > 0,
                'web_check' => Auth::guard('web')->check(),
            ]);
        };

        if (Auth::guard('web')->check()) {
            // ТЗ §4 — a live session must not outlive a block or a
            // "terminate session" action, so re-verify before trusting it.
            $liveId = (int) Auth::guard('web')->id();
            if (! $this->adminAccessStillValid($request, $liveId)) {
                $log('access_revoked_on_fast_path', ['user_id' => $liveId]);
                Auth::guard('web')->logout();
                $request->session()->forget(['admin_session_user_id', 'admin_session_started_at', '2fa_passed_at']);
                abort(404);
            }
            $log('web_guard_already_authed');
            parent::authenticate($request, $guards);
            return;
        }

        $userId = (int) $request->session()->get('admin_session_user_id', 0);
        $sourceTried = 'session';

        if ($userId <= 0) {
            $userId = $this->resolveFromJwtCookie($request);
            $sourceTried = 'jwt_cookie';
        }

        if ($userId <= 0) {
            $log('no_user_resolved', ['tried' => $sourceTried]);
            abort(404);
        }

        // Match AdminWebGuard exactly: only is_ban + role_id, no is_active gate.
        $minRole = (int) config('admin.min_role_id', 1);
        $row = DB::table('users')
            ->select('id', 'role_id', 'is_ban', 'admin_blocked_at', 'admin_sessions_revoked_at')
            ->where('id', $userId)
            ->first();

        if (! $row
            || (int) $row->is_ban === 1
            // ТЗ §4 — blocked from the panel (storefront account stays usable)
            || $row->admin_blocked_at !== null
            || (int) $row->role_id < $minRole) {
            $log('role_check_failed', [
                'user_id' => $userId,
                'source' => $sourceTried,
                'row_exists' => (bool) $row,
                'role_id' => $row->role_id ?? null,
                'min_role' => $minRole,
                'is_ban' => $row->is_ban ?? null,
            ]);
            $request->session()->forget('admin_session_user_id');
            abort(404);
        }

        // A session issued before the revocation epoch must not be resurrected
        // from the long-lived JWT cookie either.
        $revoked = (int) ($row->admin_sessions_revoked_at ?? 0);
        if ($revoked > 0 && $sourceTried === 'jwt_cookie' && $this->jwtIssuedAt($request) < $revoked) {
            $log('jwt_predates_revocation', ['user_id' => $userId, 'revoked_at' => $revoked]);
            abort(404);
        }

        $log('admitting_user', ['user_id' => $userId, 'source' => $sourceTried]);
        Auth::guard('web')->loginUsingId($userId);
        $request->session()->put('admin_session_user_id', $userId);
        $request->session()->put('admin_session_started_at', time());
        $this->recordAdminLogin($userId, $request);

        parent::authenticate($request, $guards);
    }

    /**
     * ТЗ §4 — is this already-authenticated admin still allowed in?
     * False when they were blocked, banned, demoted, or when their sessions
     * were revoked after this session started.
     */
    private function adminAccessStillValid(Request $request, int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $row = DB::table('users')
            ->select('role_id', 'is_ban', 'admin_blocked_at', 'admin_sessions_revoked_at')
            ->where('id', $userId)
            ->first();

        if (! $row
            || (int) $row->is_ban === 1
            || $row->admin_blocked_at !== null
            || (int) $row->role_id < (int) config('admin.min_role_id', 1)) {
            return false;
        }

        $revoked = (int) ($row->admin_sessions_revoked_at ?? 0);
        if ($revoked > 0) {
            // Sessions started before the revocation epoch are dead. A session
            // with no recorded start predates the feature — treat it as dead.
            $startedAt = (int) $request->session()->get('admin_session_started_at', 0);
            if ($startedAt < $revoked) {
                return false;
            }
        }

        return true;
    }

    /** Issue time of the session_token JWT, or 0 when unavailable. */
    private function jwtIssuedAt(Request $request): int
    {
        $token = $request->cookie('session_token');
        if (! $token) {
            return 0;
        }

        try {
            return (int) \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload()->get('iat');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Remember that this account entered the admin panel, so the
     * «Администраторы» tab can list past panel users. Written with the query
     * builder on purpose: the User model is audited, and an Eloquent save here
     * would add a maintenance-log row on every panel session.
     */
    private function recordAdminLogin(int $userId, Request $request): void
    {
        try {
            $last = (int) DB::table('users')->where('id', $userId)->value('last_admin_login_at');
            if ($last > time() - 300) {
                return; // already counted this session
            }

            DB::table('users')->where('id', $userId)->update([
                'last_admin_login_at' => time(),
                'last_admin_login_ip' => (string) $request->ip(),
                'admin_login_count' => DB::raw('admin_login_count + 1'),
            ]);
        } catch (\Throwable $e) {
            // never block panel entry on bookkeeping
        }
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
