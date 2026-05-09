<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MaintenanceLog;
use App\Models\RolePermission;
use App\Services\ForcePasswordResetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Force-password-reset endpoints.
 *
 * Admin-side:
 *   POST /api/members/{id}/force-password-reset  — reset for ordinary user
 *   POST /api/admins/{id}/force-password-reset   — reset for another admin (super-admin only)
 *   POST /api/members/{id}/force-password-reset/cancel
 *
 * Public-side (no auth, token only):
 *   POST /api/password-reset/{token}             — submit new password
 */
class PasswordResetController extends Controller
{
    public $user;

    public function __construct(private readonly ForcePasswordResetService $svc)
    {
        $this->middleware(function (Request $request, $next) {
            $this->user = Auth::user();
            return $next($request);
        })->except(['complete']);
    }

    private function canOrThrow(string $permission): void
    {
        $row = RolePermission::getByPermission($this->user->role_id, $permission);
        if (!$row || !$row->allow) {
            throw new Exception('Access Denied');
        }
    }

    private function logAudit(string $action, int $targetUserId, array $details, Request $request): void
    {
        try {
            MaintenanceLog::record(
                (int) ($this->user->id ?? 0),
                $action,
                'user:' . $targetUserId,
                1,
                $details,
                $request->ip()
            );
        } catch (\Throwable $e) {
            // Audit log best-effort
        }
    }

    public function forceForMember(int $id, Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('members.force_password_reset');

            if ($this->svc->isAdminRow($id)) {
                throw new Exception('Этот пользователь — администратор. Используйте раздел админов.');
            }
            if ((int) $id === (int) $this->user->id) {
                throw new Exception('Нельзя сбросить пароль самому себе через админский эндпоинт.');
            }

            $result = $this->svc->start($id, (int) $this->user->id);

            $this->logAudit('password_reset.forced.member', $id, [
                'expires_at' => $result['expires_at'],
                'email_to'   => substr($result['email'], 0, 3) . '***',
            ], $request);

            return response()->json([
                'ok' => true,
                'description' => 'Сброс инициирован. Пользователь получит письмо со ссылкой.',
                'result' => [
                    'expires_at' => $result['expires_at'],
                ],
            ]);
        } catch (RuntimeException|Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function forceForAdmin(int $id, Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('admins.force_password_reset');

            if (!$this->svc->isAdminRow($id)) {
                throw new Exception('Целевой пользователь не является администратором.');
            }
            if ((int) $id === (int) $this->user->id) {
                throw new Exception('Нельзя сбросить пароль самому себе.');
            }

            $result = $this->svc->start($id, (int) $this->user->id);

            $this->logAudit('password_reset.forced.admin', $id, [
                'expires_at' => $result['expires_at'],
                'email_to'   => substr($result['email'], 0, 3) . '***',
            ], $request);

            return response()->json([
                'ok' => true,
                'description' => 'Сброс инициирован. Администратор получит письмо со ссылкой.',
                'result' => [
                    'expires_at' => $result['expires_at'],
                ],
            ]);
        } catch (RuntimeException|Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function cancelForMember(int $id, Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('members.force_password_reset');
            $cancelled = $this->svc->cancel($id);

            if ($cancelled) {
                $this->logAudit('password_reset.cancelled', $id, [], $request);
            }

            return response()->json([
                'ok' => true,
                'description' => $cancelled ? 'Сброс отменён.' : 'Активного сброса не было.',
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    /** Public endpoint (no auth) — completes the reset given a valid token. */
    public function complete(string $token, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'new_password' => 'required|string|min:' . ForcePasswordResetService::PASSWORD_MIN
                                . '|max:' . ForcePasswordResetService::PASSWORD_MAX,
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $result = $this->svc->complete($token, (string) $request->input('new_password'));

            return response()->json([
                'ok' => true,
                'description' => 'Пароль успешно изменён. Войдите в систему с новым паролем.',
                'result' => ['user_id' => $result['user_id']],
            ]);
        } catch (RuntimeException|Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
}
