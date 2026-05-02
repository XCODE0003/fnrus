<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Allow request only if the client IP matches the configured allow-list.
 *
 * Sources:
 *   1. Static — config('admin.allowed_ips') (env ADMIN_ALLOWED_IPS, CIDR).
 *   2. Dynamic — `new_admin_allowed_ips` table (managed via admin UI),
 *      records with NULL or future `expires_at`.
 *
 * If both sources are empty, the check is disabled (allow all).
 *
 * Reads $request->ip(), which honours TrustProxies for X-Forwarded-For.
 * The dynamic list is cached for 30s to avoid hitting MySQL per request.
 */
class IPWhitelist
{
    private const CACHE_KEY = 'admin.allowed_ips.dynamic';
    private const CACHE_TTL = 30;

    public function handle(Request $request, Closure $next)
    {
        $allowed = array_merge(
            (array) config('admin.allowed_ips', []),
            $this->dynamicAllowed()
        );

        if (empty($allowed)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if ($clientIp !== null && IpUtils::checkIp($clientIp, $allowed)) {
            return $next($request);
        }

        \Log::channel(config('logging.default'))->warning('admin.ip_denied', [
            'ip' => $clientIp,
            'ua' => substr((string) $request->userAgent(), 0, 200),
            'path' => $request->path(),
        ]);

        abort(403, 'Forbidden');
    }

    /** @return array<int,string> */
    public static function dynamicAllowed(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, static function (): array {
            try {
                $now = time();
                return DB::table('admin_allowed_ips')
                    ->where(static function ($q) use ($now) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                    })
                    ->pluck('cidr')
                    ->all();
            } catch (\Throwable $e) {
                // Table may not exist yet during migration; be conservative.
                return [];
            }
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
