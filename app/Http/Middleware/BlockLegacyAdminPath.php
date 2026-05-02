<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Make legacy / predictable admin paths unconditionally return 404.
 *
 * Activates only when ADMIN_URL_PREFIX is set to a non-default value;
 * otherwise the legacy /admin prefix is the configured prefix and must
 * stay reachable. Also rejects /wp-admin, /administrator and friends
 * regardless of configuration so the surface looks like a non-CMS site.
 */
class BlockLegacyAdminPath
{
    private const ALWAYS_BLOCKED = [
        'wp-admin',
        'wp-login',
        'wp-login.php',
        'administrator',
        'phpmyadmin',
        'pma',
        'manager',
        'cms',
        'backend',
        'control',
        'console',
        'admin.php',
    ];

    public function handle(Request $request, Closure $next)
    {
        $path = ltrim($request->path(), '/');
        $first = strtolower(strtok($path, '/'));

        if (in_array($first, self::ALWAYS_BLOCKED, true)) {
            abort(404);
        }

        $configured = trim((string) config('admin.prefix', 'admin'), '/');
        if ($configured !== '' && $configured !== 'admin' && $first === 'admin') {
            abort(404);
        }

        return $next($request);
    }
}
