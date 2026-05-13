<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Force the request to appear as HTTPS when APP_FORCE_HTTPS=true.
 *
 * Production nginx in Hestia terminates TLS but proxies upstream as
 * plain HTTP and does NOT forward `X-Forwarded-Proto`, so Symfony's
 * `Request::isSecure()` reports false. That breaks signed URLs (e.g.
 * `/livewire/upload-file`) because the URL is generated as https://
 * via URL::forceScheme but verified against http:// from the inbound
 * request → signature mismatch → 401.
 *
 * Patching the nginx template requires server access; this middleware
 * solves it inside the app by stamping the request as secure before
 * any other middleware reads `isSecure()`.
 */
class ForceHttpsScheme
{
    public function handle(Request $request, Closure $next)
    {
        if (! filter_var(env('APP_FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        $request->server->set('HTTPS', 'on');
        $request->server->set('SERVER_PORT', 443);
        $request->headers->set('X-Forwarded-Proto', 'https');

        return $next($request);
    }
}
