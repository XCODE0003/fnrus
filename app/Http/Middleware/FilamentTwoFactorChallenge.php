<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Filament-side 2FA gate (mirror of App\Http\Middleware\RequireTwoFactor
 * which guards /api/admin/*). Runs on every panel request and, once a user
 * is authenticated, redirects them to the challenge page until they enter
 * a valid TOTP code. Session marker "2fa_passed_at" keeps them in for 12h.
 *
 * No-ops:
 *   - if 2FA is disabled globally (config('admin.two_factor.enforced'))
 *   - if request path IS the challenge page itself or login/logout
 *   - if user is anonymous (Filament's own auth middleware will redirect)
 *   - if user does not have 2FA configured (we let them in with a banner;
 *     setup is offered separately and is out of scope for this slice).
 */
class FilamentTwoFactorChallenge
{
    private const SESSION_KEY = '2fa_passed_at';
    private const TTL_SECONDS = 43200; // 12h, как в RequireTwoFactor

    public function handle(Request $request, Closure $next)
    {
        if (! config('admin.two_factor.enforced', true)) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();
        if ($user === null) {
            return $next($request);
        }

        // Admins only — same gate as canAccessPanel().
        $minRole = (int) config('admin.min_role_id', 1);
        if ((int) ($user->role_id ?? 0) < $minRole) {
            return $next($request);
        }

        // Allow the challenge page itself, login, logout, livewire endpoints
        // and asset routes — otherwise we'd loop.
        if ($this->isAllowlistedPath($request)) {
            return $next($request);
        }

        $hasSecret = ! empty($user->two_factor_secret) && ! empty($user->two_factor_confirmed_at);
        if (! $hasSecret) {
            $panelPath = trim((string) config('filament.path', env('FILAMENT_PATH', 'admin')), '/');
            return redirect('/' . $panelPath . '/two-factor/setup');
        }

        $now = time();

        // Fast path: session marker fresh.
        $sessionAt = (int) ($request->session()->get(self::SESSION_KEY) ?? 0);
        if ($sessionAt > 0 && ($now - $sessionAt) < self::TTL_SECONDS) {
            return $next($request);
        }

        // Persistent path: saved on user row (survives Laravel session GC).
        $userAt = (int) ($user->two_factor_passed_at ?? 0);
        if ($userAt > 0 && ($now - $userAt) < self::TTL_SECONDS) {
            $request->session()->put(self::SESSION_KEY, $userAt);
            return $next($request);
        }

        $panelPath = trim((string) config('filament.path', env('FILAMENT_PATH', 'admin')), '/');

        // Remember the URL the user actually wanted so the challenge page
        // can bounce them back after a successful code entry.
        if ($request->isMethod('GET')) {
            $request->session()->put('2fa_redirect', $request->fullUrl());
        }

        return redirect('/' . $panelPath . '/two-factor');
    }

    private function isAllowlistedPath(Request $request): bool
    {
        $panelPath = trim((string) config('filament.path', env('FILAMENT_PATH', 'admin')), '/');

        $path = trim($request->path(), '/');
        $allowExactSuffixes = ['two-factor', 'two-factor/setup', 'login', 'logout'];

        foreach ($allowExactSuffixes as $suffix) {
            if ($path === $panelPath . '/' . $suffix) {
                return true;
            }
        }

        // Livewire / asset prefixes Filament uses for the panel chrome.
        if (str_starts_with($path, 'livewire/')) {
            return true;
        }
        if (str_starts_with($path, 'filament/')) {
            return true;
        }

        return false;
    }
}
