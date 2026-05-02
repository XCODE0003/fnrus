<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IPWhitelist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Endpoints for the main administrator to manage:
 *   - dynamic IP allow-list (add/list/delete entries with optional TTL)
 *   - unlock brute-forced accounts (clear LoginThrottle cache keys)
 *   - browse the login_attempts audit log
 *
 * All routes are gated by `auth + admin + 2fa` and additionally require
 * role_id >= MAIN_ADMIN_ROLE_ID (default 2 — admin role; 3 = super-admin only).
 */
class AdminAccessController extends Controller
{
    public function ips(Request $request)
    {
        $this->guard();

        $rows = DB::table('admin_allowed_ips')->orderBy('id', 'desc')->get();
        return response()->json([
            'ok' => true,
            'result' => [
                'static_env' => (array) config('admin.allowed_ips', []),
                'dynamic'    => $rows,
                'now'        => time(),
            ],
        ]);
    }

    public function addIp(Request $request)
    {
        $this->guard();

        $cidr = trim((string) $request->input('cidr', ''));
        $note = trim((string) $request->input('note', ''));
        $ttlMinutes = (int) $request->input('ttl_minutes', 0);

        if (!$this->isValidCidr($cidr)) {
            return response()->json(['ok' => false, 'description' => 'Некорректный IP / CIDR.'], 422);
        }

        $expiresAt = $ttlMinutes > 0 ? time() + $ttlMinutes * 60 : null;

        try {
            DB::table('admin_allowed_ips')->insert([
                'cidr'       => $cidr,
                'note'       => mb_substr($note, 0, 255),
                'created_by' => (int) (Auth::user()->id ?? 0),
                'expires_at' => $expiresAt,
                'created_at' => time(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'description' => 'Запись уже существует.'], 200);
        }

        IPWhitelist::flushCache();
        Log::warning('admin.ip_allow.added', [
            'cidr' => $cidr, 'by' => Auth::user()->id ?? null, 'ttl_min' => $ttlMinutes,
        ]);

        return response()->json(['ok' => true, 'description' => 'IP добавлен.']);
    }

    public function deleteIp(Request $request, int $id)
    {
        $this->guard();

        $row = DB::table('admin_allowed_ips')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'description' => 'Не найдено.'], 404);
        }

        DB::table('admin_allowed_ips')->where('id', $id)->delete();
        IPWhitelist::flushCache();

        Log::warning('admin.ip_allow.removed', [
            'cidr' => $row->cidr, 'by' => Auth::user()->id ?? null,
        ]);
        return response()->json(['ok' => true]);
    }

    /**
     * Clear LoginThrottle counters / lockout for an (ip, username) pair
     * so a locked-out user can try again immediately.
     */
    public function unlockLogin(Request $request)
    {
        $this->guard();

        $ip = trim((string) $request->input('ip', ''));
        $username = trim((string) $request->input('username', ''));
        if ($ip === '' || $username === '') {
            return response()->json(['ok' => false, 'description' => 'Укажите ip и username.'], 422);
        }

        $key = sha1(strtolower($username) . '|' . $ip);
        Cache::forget("login_fails:{$key}");
        Cache::forget("login_lock:{$key}");

        Log::warning('admin.login_unlock', [
            'ip' => $ip, 'username' => $username, 'by' => Auth::user()->id ?? null,
        ]);

        return response()->json(['ok' => true, 'description' => 'Блокировка снята.']);
    }

    public function loginAttempts(Request $request)
    {
        $this->guard();

        $limit = min(500, max(10, (int) $request->input('limit', 100)));
        $rows = DB::table('login_attempts')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        return response()->json(['ok' => true, 'result' => $rows]);
    }

    private function guard(): void
    {
        $user = Auth::user();
        $min = (int) env('MAIN_ADMIN_ROLE_ID', 2);
        if (!$user || (int) ($user->role_id ?? 0) < $min) {
            abort(response()->json(['ok' => false, 'description' => 'Только для главного администратора.'], 403));
        }
    }

    private function isValidCidr(string $cidr): bool
    {
        if ($cidr === '') {
            return false;
        }
        $parts = explode('/', $cidr, 2);
        $ip = $parts[0];
        $mask = $parts[1] ?? null;
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        if ($mask !== null) {
            if (!ctype_digit($mask)) {
                return false;
            }
            $maxBits = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 32;
            if ((int) $mask < 0 || (int) $mask > $maxBits) {
                return false;
            }
        }
        return true;
    }
}
