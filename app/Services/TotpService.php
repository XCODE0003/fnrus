<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Self-contained TOTP (RFC 6238) service. No external dependency, works
 * fully offline — TOTP-compatible with Google Authenticator, Yandex Key,
 * Microsoft Authenticator, Authy.
 *
 * Default: SHA1, 6 digits, 30s step, ±1 step window (60s tolerance).
 *
 * Secrets are stored encrypted via Laravel Crypt. Recovery codes are
 * stored as bcrypt hashes (verified one-by-one then invalidated).
 */
class TotpService
{
    private const DIGITS = 6;
    private const PERIOD = 30;
    private const ALGO   = 'sha1';

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function provisioningUri(string $accountLabel, string $secret, ?string $issuer = null): string
    {
        $issuer = $issuer ?: (string) config('admin.two_factor.issuer', config('app.name', 'App'));
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountLabel);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => strtoupper(self::ALGO),
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ]);
        return "otpauth://totp/{$label}?{$params}";
    }

    public function verify(string $secret, string $code, ?int $window = null): bool
    {
        $window = $window ?? (int) config('admin.two_factor.window', 1);
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }

        $timeSlice = (int) floor(time() / self::PERIOD);
        try {
            for ($i = -$window; $i <= $window; $i++) {
                if (hash_equals($this->codeAt($secret, $timeSlice + $i), $code)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Corrupt or non-Base32 secret stored in users.two_factor_secret
            // (e.g. a stale debugging placeholder). Don't 500 the challenge
            // page — return false so the user is treated as failed-code,
            // and the bridge / setup flow can recover them.
            \Log::warning('TotpService::verify: secret malformed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
        return false;
    }

    public function codeAt(string $secret, int $timeSlice): string
    {
        $key = $this->base32Decode($secret);
        $bin = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac(self::ALGO, $bin, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncated = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );
        $code = $truncated % (10 ** self::DIGITS);
        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** @return array<int,string> 10 plain recovery codes (xxxx-xxxx-xxxx). */
    public function generateRecoveryCodes(?int $count = null): array
    {
        $count = $count ?: (int) config('admin.two_factor.recovery_count', 10);
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = sprintf(
                '%s-%s-%s',
                strtolower(bin2hex(random_bytes(2))),
                strtolower(bin2hex(random_bytes(2))),
                strtolower(bin2hex(random_bytes(2)))
            );
        }
        return $codes;
    }

    /** @param array<int,string> $codes  @return array<int,string> bcrypt hashes */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(static fn (string $c) => password_hash($c, PASSWORD_BCRYPT), $codes);
    }

    /**
     * Verify a recovery code and remove it from the hash list.
     *
     * @param array<int,string> $hashes
     * @return array<int,string>|null  new hash list with consumed code removed,
     *                                 or null if no match.
     */
    public function consumeRecoveryCode(string $code, array $hashes): ?array
    {
        $code = preg_replace('/\s+/', '', strtolower($code));
        foreach ($hashes as $i => $hash) {
            if (password_verify($code, $hash)) {
                unset($hashes[$i]);
                return array_values($hashes);
            }
        }
        return null;
    }

    public function encryptSecret(string $secret): string
    {
        return Crypt::encryptString($secret);
    }

    public function decryptSecret(string $cipher): string
    {
        return Crypt::decryptString($cipher);
    }

    private function base32Encode(string $bytes): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($bytes) as $b) {
            $bits .= str_pad(decbin(ord($b)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $out .= $alphabet[bindec($chunk)];
        }
        return $out;
    }

    private function base32Decode(string $base32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper(rtrim($base32, '='));
        $bits = '';
        foreach (str_split($base32) as $c) {
            $idx = strpos($alphabet, $c);
            if ($idx === false) {
                throw new \InvalidArgumentException('Invalid base32 character');
            }
            $bits .= str_pad(decbin($idx), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $out .= chr(bindec($chunk));
            }
        }
        return $out;
    }
}
