<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Records a login into `login_attempts` — the table «Контроль доступа» reads.
 *
 * That table used to be written by exactly one place: the LoginThrottle
 * middleware on POST /api/auth/login. Every other way into an account —
 * Telegram, Yandex OAuth, the e-mail magic link — bypassed it, so those
 * sign-ins were never recorded and the page looked like it had stopped
 * working (the last password login here was 2026-06-07).
 *
 * Keep the row shape identical to LoginThrottle::log().
 */
class LoginAudit
{
    /**
     * @param string $username identifier the page matches on — pass the
     *                         account's email or username, NOT a Telegram id,
     *                         or «Контроль доступа» will not match it to an
     *                         admin and will hide the row.
     * @param string $reason   short machine-ish tag, e.g. 'telegram', 'yandex'
     */
    public static function record(string $username, bool $ok, string $reason, ?string $ip = null, ?string $userAgent = null): void
    {
        $entry = [
            'username'   => substr($username, 0, 255),
            'ip'         => substr((string) ($ip ?? request()->ip()), 0, 45),
            'user_agent' => substr((string) ($userAgent ?? request()->userAgent()), 0, 500),
            'successful' => $ok ? 1 : 0,
            'reason'     => substr($reason, 0, 255),
        ];

        try {
            DB::table('login_attempts')->insert($entry + ['created_at' => date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            Log::warning('login_attempts insert failed: ' . $e->getMessage());
        }

        try {
            Log::channel('login_attempts')->info(($ok ? 'OK ' : 'FAIL ') . $reason, $entry);
        } catch (\Throwable $e) {
            // channel may be misconfigured — never break the login for logging
        }
    }

    /** Convenience: log a successful sign-in for a User-ish row. */
    public static function success(object $user, string $reason): void
    {
        self::record(
            (string) ($user->email ?: $user->username ?: ('user#' . ($user->id ?? '?'))),
            true,
            $reason
        );
    }
}
