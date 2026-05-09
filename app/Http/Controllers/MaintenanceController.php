<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceLog;
use App\Models\RolePermission;
use App\Services\AnalyticsResetService;
use App\Services\BulkCleanupService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Admin maintenance: analytics reset and bulk-data cleanup.
 *
 * Every destructive endpoint:
 *   1. Verifies the admin's RolePermission ('maintenance.*').
 *   2. Requires a confirmation phrase in the body — protects against
 *      accidental clicks and CSRF replays via the JWT cookie.
 *   3. Persists a MaintenanceLog row (before/after snapshot, IP).
 *
 * Routes are mounted under /api/maintenance/* in routes/api.php.
 */
class MaintenanceController extends Controller
{
    public const CONFIRM_PHRASE = 'СБРОСИТЬ';

    public $user;

    public function __construct(
        private readonly AnalyticsResetService $analytics,
        private readonly BulkCleanupService    $cleanup,
    ) {
        $this->middleware(function (Request $request, $next) {
            $this->user = Auth::user();
            return $next($request);
        });
    }

    /** @throws Exception */
    private function canOrThrow(string $permission): void
    {
        $row = RolePermission::getByPermission($this->user->role_id, $permission);
        if (!$row || !$row->allow) {
            throw new Exception('Access Denied');
        }
    }

    private function assertConfirmed(Request $request): void
    {
        if ($request->input('confirm') !== self::CONFIRM_PHRASE) {
            throw new Exception('Введите подтверждение «' . self::CONFIRM_PHRASE . '» для запуска операции.');
        }
    }

    private function logAction(string $action, ?string $target, int $affected, array $details, Request $request): void
    {
        try {
            MaintenanceLog::record(
                (int) ($this->user->id ?? 0),
                $action,
                $target,
                $affected,
                $details,
                $request->ip()
            );
        } catch (\Throwable $e) {
            Log::warning('MaintenanceLog write failed', ['error' => $e->getMessage()]);
        }
    }

    /* ===================== Analytics reset ===================== */

    public function stats(): JsonResponse
    {
        try {
            $this->canOrThrow('maintenance.view');

            $orderCounts = DB::table('orders')
                ->selectRaw('status, COUNT(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status');

            return response()->json([
                'ok' => true,
                'result' => [
                    'analytics' => $this->analytics->collectTotals(),
                    'orders' => [
                        'paid'     => (int) ($orderCounts[2] ?? 0),
                        'expired'  => (int) ($orderCounts[4] ?? 0),
                        'cancelled'=> (int) ($orderCounts[3] ?? 0),
                        'pending'  => (int) ($orderCounts[1] ?? 0),
                    ],
                    'coupons'         => DB::table('coupons')->count(),
                    'attachments'     => \Schema::hasTable('attachments') ? DB::table('attachments')->count() : 0,
                    'exports'         => \Schema::hasTable('materials_exports') ? DB::table('materials_exports')->count() : 0,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function resetAnalytics(Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('maintenance.analytics_reset');
            $this->assertConfirmed($request);

            $validator = Validator::make($request->all(), [
                'scope' => 'required|in:' . implode(',', AnalyticsResetService::VALID_SCOPES),
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $result = $this->analytics->reset($request->input('scope'));

            $this->logAction('analytics.reset', $result['scope'], $result['affected'], [
                'before' => $result['before'],
                'after'  => $result['after'],
            ], $request);

            return response()->json([
                'ok'          => true,
                'description' => 'Аналитика сброшена. Затронуто записей: ' . $result['affected'],
                'result'      => $result,
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    /* ===================== Bulk cleanups ===================== */

    public function cleanupOrders(Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('maintenance.cleanup_orders');
            $this->assertConfirmed($request);

            $validator = Validator::make($request->all(), [
                'type'  => 'required|in:' . implode(',', BulkCleanupService::VALID_ORDER_TYPES),
                'limit' => 'required|integer|min:1|max:' . BulkCleanupService::MAX_PER_CALL,
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $deleted = $this->cleanup->cleanupOrders($request->input('type'), (int) $request->input('limit'));

            $this->logAction('cleanup.orders', $request->input('type'), $deleted, [
                'requested_limit' => (int) $request->input('limit'),
            ], $request);

            return response()->json([
                'ok'          => true,
                'description' => 'Удалено заказов: ' . $deleted,
                'result'      => ['deleted' => $deleted],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function cleanupCoupons(Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('maintenance.cleanup_coupons');
            $this->assertConfirmed($request);

            $validator = Validator::make($request->all(), [
                'limit' => 'required|integer|min:1|max:' . BulkCleanupService::MAX_PER_CALL,
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $deleted = $this->cleanup->cleanupCoupons((int) $request->input('limit'));

            $this->logAction('cleanup.coupons', null, $deleted, [
                'requested_limit' => (int) $request->input('limit'),
            ], $request);

            return response()->json([
                'ok'          => true,
                'description' => 'Удалено промокодов: ' . $deleted,
                'result'      => ['deleted' => $deleted],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function cleanupFiles(Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('maintenance.cleanup_files');
            $this->assertConfirmed($request);

            $validator = Validator::make($request->all(), [
                'type'  => 'required|in:' . implode(',', BulkCleanupService::VALID_FILE_TYPES),
                'limit' => 'required|integer|min:1|max:' . BulkCleanupService::MAX_PER_CALL,
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $r = $this->cleanup->cleanupFiles($request->input('type'), (int) $request->input('limit'));

            $this->logAction('cleanup.files', $request->input('type'), $r['rows'], [
                'requested_limit' => (int) $request->input('limit'),
                'files_removed'   => $r['files'],
                'files_missing'   => $r['missing'],
            ], $request);

            return response()->json([
                'ok'          => true,
                'description' => 'Удалено записей: ' . $r['rows'] . ', файлов: ' . $r['files'],
                'result'      => $r,
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function cleanupExports(Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('maintenance.cleanup_exports');
            $this->assertConfirmed($request);

            $validator = Validator::make($request->all(), [
                'limit' => 'required|integer|min:1|max:' . BulkCleanupService::MAX_PER_CALL,
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $deleted = $this->cleanup->cleanupExports((int) $request->input('limit'));

            $this->logAction('cleanup.exports', null, $deleted, [
                'requested_limit' => (int) $request->input('limit'),
            ], $request);

            return response()->json([
                'ok'          => true,
                'description' => 'Удалено записей экспорта: ' . $deleted,
                'result'      => ['deleted' => $deleted],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    /* ===================== Audit log ===================== */

    public function auditLog(Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('maintenance.view');

            $limit = max(1, min(200, (int) $request->input('limit', 50)));

            $rows = MaintenanceLog::orderByDesc('id')
                ->limit($limit)
                ->get();

            return response()->json(['ok' => true, 'result' => $rows]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
}
