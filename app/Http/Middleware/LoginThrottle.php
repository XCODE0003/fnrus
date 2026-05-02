<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Brute-force protection for the login endpoint.
 *
 * Rules (configurable in config/admin.php):
 *   - Max 5 failed attempts per (ip + username) in 15 minutes.
 *   - On exceed, lock the (ip + username) pair for 30 minutes.
 *   - After 3 failures the response carries "captcha_required" so the UI
 *     can show hCaptcha. The login controller already validates the
 *     captcha when 'h-captcha-response' is provided and CAPTCHA_ENABLED
 *     is on; this middleware adds the toggle in the response.
 *   - Failed responses get a 2-second sleep before being returned.
 *   - Every attempt (success or failure) is appended to new_login_attempts.
 */
class LoginThrottle
{
    public function handle(Request $request, Closure $next)
    {
        $cfg = (array) config('admin.login', []);
        $max         = (int) ($cfg['max_attempts'] ?? 5);
        $decayMin    = (int) ($cfg['decay_minutes'] ?? 15);
        $lockoutMin  = (int) ($cfg['lockout_minutes'] ?? 30);
        $captchaAt   = (int) ($cfg['captcha_after'] ?? 3);
        $delayMs     = (int) ($cfg['fail_delay_ms'] ?? 2000);

        $username = (string) $request->input('username', '');
        $ip = (string) $request->ip();
        $key = $this->key($ip, $username);
        $lockKey = "login_lock:{$key}";

        if (Cache::has($lockKey)) {
            $this->log($username, $ip, $request, false, 'locked');
            return response()->json([
                'ok' => false,
                'description' => 'Слишком много неудачных попыток. Попробуйте через ' . $lockoutMin . ' мин.',
                'locked' => true,
            ], 429);
        }

        $attempts = (int) Cache::get("login_fails:{$key}", 0);

        $response = $next($request);

        $isFailure = $this->isFailureResponse($response);

        if ($isFailure) {
            $attempts++;
            Cache::put("login_fails:{$key}", $attempts, now()->addMinutes($decayMin));

            $this->log($username, $ip, $request, false, 'bad_credentials');

            if ($attempts >= $max) {
                Cache::put($lockKey, 1, now()->addMinutes($lockoutMin));
                Cache::forget("login_fails:{$key}");
                $this->annotate($response, [
                    'description' => 'Превышено количество попыток. Аккаунт заблокирован на ' . $lockoutMin . ' мин.',
                    'locked' => true,
                ]);
            } elseif ($attempts >= $captchaAt) {
                $this->annotate($response, ['captcha_required' => true]);
            }

            usleep($delayMs * 1000);
        } else {
            // Success — clear counters and any stale 2FA session marker
            // (forces fresh TOTP verification for the new login).
            Cache::forget("login_fails:{$key}");
            try { $request->session()->forget('2fa_passed_at'); } catch (\Throwable $e) {}
            $this->log($username, $ip, $request, true, 'ok');
        }

        return $response;
    }

    private function key(string $ip, string $username): string
    {
        return sha1(strtolower(trim($username)) . '|' . $ip);
    }

    private function isFailureResponse($response): bool
    {
        if (!method_exists($response, 'getContent')) {
            return false;
        }
        $body = (string) $response->getContent();
        if ($body === '' || $body[0] !== '{') {
            return false;
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            return false;
        }
        return array_key_exists('ok', $json) && $json['ok'] === false;
    }

    private function annotate($response, array $extra): void
    {
        $body = (string) $response->getContent();
        $json = json_decode($body, true);
        if (!is_array($json)) {
            return;
        }
        $response->setContent(json_encode(array_merge($json, $extra), JSON_UNESCAPED_UNICODE));
    }

    private function log(string $username, string $ip, Request $request, bool $ok, string $reason): void
    {
        $entry = [
            'username'   => substr($username, 0, 255),
            'ip'         => substr($ip, 0, 45),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'successful' => $ok ? 1 : 0,
            'reason'     => $reason,
        ];

        try {
            DB::table('login_attempts')->insert($entry + ['created_at' => date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            \Log::warning('login_attempts insert failed: ' . $e->getMessage());
        }

        // Mirror to dedicated rotating file ('login_attempts' channel) so
        // the audit trail survives DB downtime / table truncation.
        try {
            \Log::channel('login_attempts')->info(($ok ? 'OK ' : 'FAIL ') . $reason, $entry);
        } catch (\Throwable $e) {
            // Channel may be misconfigured in tests — non-fatal.
        }
    }
}
