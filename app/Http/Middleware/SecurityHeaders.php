<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Apply OWASP-recommended security headers to every response.
 *
 * Notes:
 *   - HSTS is only emitted on HTTPS so local HTTP dev is unaffected.
 *   - CSP is intentionally permissive for inline styles/scripts because
 *     the existing app uses inline <script>/<style>; tighten gradually.
 *     Set ADMIN_CSP_REPORT_ONLY=false to enforce.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $headers = $response->headers;

        if (!$headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'DENY');
        }
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-XSS-Protection', '1; mode=block');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), fullscreen=(self)');

        if ($request->isSecure()) {
            $maxAge = (int) config('admin.security_headers.hsts_max_age', 31536000);
            $headers->set('Strict-Transport-Security', "max-age={$maxAge}; includeSubDomains; preload");
        }

        $cspHeader = config('admin.security_headers.csp_report_only', true)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://hcaptcha.com https://*.hcaptcha.com https://yastatic.net https://mc.yandex.ru https://code.jivo.ru https://*.jivosite.com",
            "style-src 'self' 'unsafe-inline' https://hcaptcha.com https://*.hcaptcha.com https://yastatic.net",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https:",
            "connect-src 'self' https://hcaptcha.com https://*.hcaptcha.com https://api.hcaptcha.com https://*.jivosite.com wss://*.jivosite.com https://mc.yandex.ru https://oauth.yandex.ru",
            "frame-src 'self' https://hcaptcha.com https://*.hcaptcha.com https://*.jivosite.com",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self' https://oauth.yandex.ru",
            "object-src 'none'",
            "upgrade-insecure-requests",
        ]);

        if (!$headers->has($cspHeader)) {
            $headers->set($cspHeader, $csp);
        }

        return $response;
    }
}
