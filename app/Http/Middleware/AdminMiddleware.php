<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Admin/staff role gate. Roles are stored in `new_users.role_id`:
 *   1 = moderator, 2 = admin, 3 = super-admin (seeded in new_roles).
 *
 * Default minimum role is configurable via config('admin.min_role_id')
 * (env ADMIN_MIN_ROLE_ID). It defaults to 1 to preserve legacy behaviour;
 * tighten to 2 in production to keep moderators out of admin endpoints.
 *
 * This middleware does NOT enforce 2FA — that is handled by RequireTwoFactor.
 */
class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        $min = (int) config('admin.min_role_id', env('ADMIN_MIN_ROLE_ID', 1));

        // ТЗ §4 — a blocked (or banned) admin must lose the /api/admin/*
        // surface too, otherwise the block is cosmetic: their 30-day JWT would
        // still carry full write access.
        if ($user
            && (int) ($user->role_id ?? 0) >= $min
            && (int) ($user->is_ban ?? 0) !== 1
            && ($user->admin_blocked_at ?? null) === null) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['ok' => false, 'message' => 'Access Denied'], 403);
        }

        abort(404);
    }
}
