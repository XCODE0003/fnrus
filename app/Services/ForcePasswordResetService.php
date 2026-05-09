<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Force-password-reset workflow.
 *
 *   start($userId, $forcedByAdminId) → marks the row, generates a
 *   token, emails the link. Login is rejected until completed.
 *
 *   complete($token, $newPassword) → verifies token+expiry, hashes
 *   the new password, clears the force-reset flag.
 */
class ForcePasswordResetService
{
    public const TOKEN_TTL_HOURS = 24;
    public const PASSWORD_MIN = 8;
    public const PASSWORD_MAX = 128;

    /**
     * @return array{token: string, expires_at: int, email: string}
     */
    public function start(int $userId, int $forcedByAdminId): array
    {
        $row = DB::table('users')->where('id', $userId)->first();
        if (!$row) {
            throw new RuntimeException('Пользователь не найден');
        }
        if (empty($row->email)) {
            throw new RuntimeException('У пользователя не задан email — сброс невозможен.');
        }

        $token = Str::random(64);
        $expiresAt = time() + (self::TOKEN_TTL_HOURS * 3600);

        DB::table('users')->where('id', $userId)->update([
            'force_password_reset'      => 1,
            'password_reset_token'      => $token,
            'password_reset_expires_at' => $expiresAt,
            'password_reset_forced_by'  => $forcedByAdminId,
        ]);

        $resetUrl = rtrim(config('app.url') ?: '', '/') . '/password-reset/' . $token;

        try {
            Mail::send(
                'emails.forced-reset',
                [
                    'reset_url'   => $resetUrl,
                    'valid_hours' => self::TOKEN_TTL_HOURS,
                ],
                function ($message) use ($row) {
                    $message->to($row->email, $row->username ?? '')
                        ->subject('Сброс пароля по требованию администратора');
                }
            );
        } catch (\Throwable $e) {
            // Token is already persisted; the operator can re-issue or share the URL out-of-band.
            Log::warning('ForcePasswordResetService: email send failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
        }

        return [
            'token'      => $token,
            'expires_at' => $expiresAt,
            'email'      => (string) $row->email,
        ];
    }

    /**
     * Verify token + expiry, then atomically swap to the new hash and
     * clear the force-reset state.
     *
     * @return array{user_id: int, email: string}
     */
    public function complete(string $token, string $newPassword): array
    {
        if (strlen($token) !== 64) {
            throw new RuntimeException('Недействительная ссылка.');
        }
        $len = mb_strlen($newPassword);
        if ($len < self::PASSWORD_MIN || $len > self::PASSWORD_MAX) {
            throw new RuntimeException(sprintf(
                'Пароль должен быть от %d до %d символов.',
                self::PASSWORD_MIN, self::PASSWORD_MAX
            ));
        }

        $row = DB::table('users')->where('password_reset_token', $token)->first();
        if (!$row) {
            throw new RuntimeException('Недействительная или уже использованная ссылка.');
        }
        if (!$row->password_reset_expires_at || $row->password_reset_expires_at < time()) {
            throw new RuntimeException('Срок действия ссылки истёк. Попросите администратора повторить сброс.');
        }

        DB::transaction(function () use ($row, $newPassword) {
            DB::table('users')->where('id', $row->id)->update([
                'password'                  => Hash::make($newPassword),
                'force_password_reset'      => 0,
                'password_reset_token'      => null,
                'password_reset_expires_at' => null,
            ]);
        });

        return ['user_id' => (int) $row->id, 'email' => (string) $row->email];
    }

    /** Cancel an outstanding reset (e.g. admin clicked by mistake). */
    public function cancel(int $userId): bool
    {
        return DB::table('users')->where('id', $userId)->update([
            'force_password_reset'      => 0,
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
            'password_reset_forced_by'  => null,
        ]) > 0;
    }

    public function isAdminRow(int $userId): bool
    {
        $minAdminRole = (int) config('admin.min_role_id', 2);
        $roleId = (int) DB::table('users')->where('id', $userId)->value('role_id');
        return $roleId >= $minAdminRole;
    }
}
