<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Require an authenticated admin to have a verified TOTP within the last
 * 12 hours (session token "2fa_passed_at"). Used on /api/admin/* routes.
 *
 * Behaviour when the check fails:
 *   - JSON: returns 423 Locked with action='2fa_required' / 'setup_required'
 *     so the SPA knows to redirect to /<admin>/2fa.
 *
 * If 2FA is not enforced globally (config('admin.two_factor.enforced') = false)
 * or the user has role_id < admin gate, the middleware no-ops.
 */
class RequireTwoFactor
{
    private const SESSION_KEY = '2fa_passed_at';
    private const TTL_SECONDS = 43200; // 12h

    public function handle(Request $request, Closure $next)
    {
        if (!config('admin.two_factor.enforced', true)) {
            return $next($request);
        }

        $user = Auth::user();
        if (!$user) {
            return $next($request);
        }

        $minRole = (int) config('admin.min_role_id', 1);
        if ((int) ($user->role_id ?? 0) < $minRole) {
            return $next($request);
        }

        $hasSecret = !empty($user->two_factor_secret) && !empty($user->two_factor_confirmed_at);

        if (!$hasSecret) {
            return $this->deny($request, 'setup_required', 'Включите двухфакторную аутентификацию.');
        }

        $now = time();

        // Fast path: session marker still warm.
        $sessionAt = (int) ($request->session()->get(self::SESSION_KEY) ?? 0);
        if ($sessionAt > 0 && ($now - $sessionAt) < self::TTL_SECONDS) {
            return $next($request);
        }

        // Persistent path: saved on the user row by TwoFactorController::verify.
        // Survives Laravel session GC (SESSION_LIFETIME=120min) since JWT
        // (15-day TTL) re-authenticates the user and we restore the marker.
        $userAt = (int) ($user->two_factor_passed_at ?? 0);
        if ($userAt > 0 && ($now - $userAt) < self::TTL_SECONDS) {
            try { $request->session()->put(self::SESSION_KEY, $userAt); } catch (\Throwable $e) {}
            return $next($request);
        }

        return $this->deny($request, '2fa_required', 'Требуется подтверждение 2FA.');
    }

    private function deny(Request $request, string $action, string $message)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'ok' => false,
                'description' => $message,
                'action' => $action,
            ], 423);
        }
        return redirect('/' . trim((string) config('admin.prefix', 'admin'), '/') . '/2fa');
    }
}
