<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin URL prefix
    |--------------------------------------------------------------------------
    | Path segment under which the admin panel is mounted. Default 'admin'
    | preserves legacy URLs. In production set ADMIN_URL_PREFIX in .env to
    | a long random string (e.g. 'secure-control-panel-8f3k9x2m7p').
    | The legacy '/admin' path is unconditionally returned as 404 (see
    | App\Http\Middleware\BlockLegacyAdminPath) when the configured prefix
    | differs from 'admin'.
    */
    'prefix' => env('ADMIN_URL_PREFIX', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Minimum role_id for admin gate
    |--------------------------------------------------------------------------
    | 1 = include moderators (legacy), 2 = admins only, 3 = super-admin only.
    */
    'min_role_id' => (int) env('ADMIN_MIN_ROLE_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | IP allow-list for the admin panel
    |--------------------------------------------------------------------------
    | Comma-separated list of IPs / CIDR blocks allowed to reach the admin
    | panel. Empty = disabled (allow all). Supports IPv4 and IPv6 CIDR.
    |   Example: ADMIN_ALLOWED_IPS="203.0.113.7,198.51.100.0/24,2001:db8::/32"
    */
    'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADMIN_ALLOWED_IPS', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Login brute-force throttle
    |--------------------------------------------------------------------------
    */
    'login' => [
        'max_attempts'      => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes'     => (int) env('LOGIN_DECAY_MINUTES', 15),
        'lockout_minutes'   => (int) env('LOGIN_LOCKOUT_MINUTES', 30),
        'captcha_after'     => (int) env('LOGIN_CAPTCHA_AFTER', 3),
        'fail_delay_ms'     => (int) env('LOGIN_FAIL_DELAY_MS', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication (TOTP, RFC 6238)
    |--------------------------------------------------------------------------
    */
    'two_factor' => [
        'enforced'          => (bool) env('ADMIN_2FA_ENFORCED', true),
        'issuer'            => env('ADMIN_2FA_ISSUER', env('APP_NAME', 'Fnrus')),
        'window'            => (int) env('ADMIN_2FA_WINDOW', 1),
        'recovery_count'    => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security headers
    |--------------------------------------------------------------------------
    | CSP report-only mode lets you ship a strict policy and watch for
    | violations before enforcing. Toggle ADMIN_CSP_REPORT_ONLY=false to
    | enforce.
    */
    'security_headers' => [
        'csp_report_only' => (bool) env('ADMIN_CSP_REPORT_ONLY', true),
        'hsts_max_age'    => (int) env('HSTS_MAX_AGE', 31536000),
    ],
];
