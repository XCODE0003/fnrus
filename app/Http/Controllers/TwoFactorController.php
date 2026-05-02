<?php

namespace App\Http\Controllers;

use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * 2FA enrolment and verification (TOTP, RFC 6238).
 *
 * All endpoints assume the caller is an authenticated admin
 * (gated by the `admin` middleware in routes/api.php).
 *
 * Storage: secret + recovery codes live on the user row
 * (new_users.two_factor_secret/recovery_codes/confirmed_at).
 * The secret is encrypted at rest via Laravel Crypt.
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly TotpService $totp) {}

    public function setup(Request $request)
    {
        $user = Auth::user();
        if ($user->two_factor_confirmed_at) {
            return response()->json([
                'ok' => true,
                'result' => ['enabled' => true],
            ]);
        }

        $secret = $this->totp->generateSecret();
        $request->session()->put('2fa_pending_secret', $secret);

        return response()->json([
            'ok' => true,
            'result' => [
                'enabled' => false,
                'secret' => $secret,
                'otpauth' => $this->totp->provisioningUri(
                    $user->email ?: $user->username,
                    $secret
                ),
            ],
        ]);
    }

    public function enable(Request $request)
    {
        $user = Auth::user();
        $code = (string) $request->input('code', '');
        $secret = (string) $request->session()->get('2fa_pending_secret', '');

        if ($secret === '') {
            return response()->json(['ok' => false, 'description' => 'Сессия настройки 2FA не найдена. Откройте страницу 2FA заново.'], 200);
        }
        if (!$this->totp->verify($secret, $code)) {
            return response()->json(['ok' => false, 'description' => 'Неверный код.'], 200);
        }

        $recoveryPlain = $this->totp->generateRecoveryCodes();
        $recoveryHashed = $this->totp->hashRecoveryCodes($recoveryPlain);

        DB::table('users')->where('id', $user->id)->update([
            'two_factor_secret'         => $this->totp->encryptSecret($secret),
            'two_factor_recovery_codes' => json_encode($recoveryHashed),
            'two_factor_confirmed_at'   => time(),
        ]);

        $request->session()->forget('2fa_pending_secret');
        $request->session()->put('2fa_passed_at', time());

        return response()->json([
            'ok' => true,
            'description' => '2FA включена.',
            'result' => ['recovery_codes' => $recoveryPlain],
        ]);
    }

    public function verify(Request $request)
    {
        $user = Auth::user();
        $code = (string) $request->input('code', '');
        $useRecovery = (bool) $request->input('recovery', false);

        if (!$user->two_factor_secret || !$user->two_factor_confirmed_at) {
            return response()->json(['ok' => false, 'description' => '2FA не настроена.'], 200);
        }

        if ($useRecovery) {
            $hashes = (array) json_decode((string) $user->two_factor_recovery_codes, true);
            $remaining = $this->totp->consumeRecoveryCode($code, $hashes);
            if ($remaining === null) {
                return response()->json(['ok' => false, 'description' => 'Неверный резервный код.'], 200);
            }
            DB::table('users')->where('id', $user->id)->update([
                'two_factor_recovery_codes' => json_encode($remaining),
            ]);
        } else {
            $secret = $this->totp->decryptSecret($user->two_factor_secret);
            if (!$this->totp->verify($secret, $code)) {
                return response()->json(['ok' => false, 'description' => 'Неверный код.'], 200);
            }
        }

        $request->session()->put('2fa_passed_at', time());
        return response()->json(['ok' => true, 'description' => 'Подтверждено.']);
    }

    /**
     * Send a one-time recovery code to the admin's registered email so they
     * can reset 2FA after losing the authenticator device.
     *
     * Requires the user to be authenticated (auth+admin middleware) — the
     * caller must hold a valid JWT, so a stolen email alone cannot trigger
     * this. Throttled to 1 request / 10 minutes per account.
     */
    public function requestEmailRecovery(Request $request)
    {
        $user = Auth::user();

        if (!$user->two_factor_confirmed_at) {
            return response()->json(['ok' => false, 'description' => '2FA не настроена.'], 200);
        }
        if (!$user->email) {
            return response()->json(['ok' => false, 'description' => 'У аккаунта не указан email.'], 200);
        }

        $now = time();
        $lastRequest = (int) ($user->two_factor_recovery_email_requested_at ?? 0);
        if ($lastRequest > 0 && ($now - $lastRequest) < 600) {
            $wait = 600 - ($now - $lastRequest);
            return response()->json([
                'ok' => false,
                'description' => "Письмо уже отправлено. Повторите через {$wait} с.",
            ], 429);
        }

        $code = strtoupper(Str::random(10));
        $hash = password_hash($code, PASSWORD_BCRYPT);

        DB::table('users')->where('id', $user->id)->update([
            'two_factor_recovery_email_code'         => $hash,
            'two_factor_recovery_email_expires_at'   => $now + 1800, // 30 min
            'two_factor_recovery_email_requested_at' => $now,
        ]);

        try {
            Mail::send(
                'emails.two-factor-recovery',
                [
                    'username'      => $user->username ?: ($user->email ?? 'admin'),
                    'code'          => $code,
                    'valid_minutes' => 30,
                    'request_ip'    => $request->ip(),
                ],
                static function ($message) use ($user) {
                    $message->to($user->email, $user->username ?: 'admin')
                            ->subject('Сброс двухфакторной защиты');
                }
            );
        } catch (\Throwable $e) {
            Log::error('2fa.email_recovery.send_failed', ['user_id' => $user->id, 'err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'description' => 'Не удалось отправить письмо. Попробуйте позже.'], 500);
        }

        Log::warning('2fa.email_recovery.requested', [
            'user_id' => $user->id,
            'email'   => $this->maskEmail($user->email),
            'ip'      => $request->ip(),
        ]);

        return response()->json([
            'ok' => true,
            'description' => 'Письмо со сбросным кодом отправлено на ' . $this->maskEmail($user->email) . '.',
        ]);
    }

    /**
     * Consume the email recovery code: clears 2FA secret + recovery codes
     * so the user can re-enrol from scratch.
     */
    public function confirmEmailRecovery(Request $request)
    {
        $user = Auth::user();
        $code = (string) $request->input('code', '');

        if (!$user->two_factor_recovery_email_code || !$user->two_factor_recovery_email_expires_at) {
            return response()->json(['ok' => false, 'description' => 'Код сброса не запрошен.'], 200);
        }
        if ((int) $user->two_factor_recovery_email_expires_at < time()) {
            return response()->json(['ok' => false, 'description' => 'Код сброса истёк. Запросите новый.'], 200);
        }
        if (!password_verify($code, (string) $user->two_factor_recovery_email_code)) {
            return response()->json(['ok' => false, 'description' => 'Неверный код.'], 200);
        }

        DB::table('users')->where('id', $user->id)->update([
            'two_factor_secret'                      => null,
            'two_factor_recovery_codes'              => null,
            'two_factor_confirmed_at'                => null,
            'two_factor_recovery_email_code'         => null,
            'two_factor_recovery_email_expires_at'   => null,
            'two_factor_recovery_email_requested_at' => null,
        ]);
        $request->session()->forget(['2fa_passed_at', '2fa_pending_secret']);

        Log::warning('2fa.email_recovery.consumed', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
        ]);

        return response()->json([
            'ok' => true,
            'description' => '2FA сброшена. Настройте её заново.',
        ]);
    }

    private function maskEmail(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return '—';
        }
        [$local, $domain] = explode('@', $email, 2);
        $head = mb_substr($local, 0, 2);
        return $head . str_repeat('*', max(1, mb_strlen($local) - 2)) . '@' . $domain;
    }

    public function disable(Request $request)
    {
        $user = Auth::user();
        $code = (string) $request->input('code', '');

        if ($user->two_factor_secret) {
            $secret = $this->totp->decryptSecret($user->two_factor_secret);
            if (!$this->totp->verify($secret, $code)) {
                return response()->json(['ok' => false, 'description' => 'Неверный код.'], 200);
            }
        }

        DB::table('users')->where('id', $user->id)->update([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ]);
        $request->session()->forget('2fa_passed_at');

        return response()->json(['ok' => true, 'description' => '2FA отключена.']);
    }
}
