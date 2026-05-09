<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Resets numeric counters across the catalogue. Each public method
 * returns ['affected' => int, 'before' => array, 'after' => array]
 * so the caller can persist a before/after snapshot to the audit log.
 *
 * All operations are wrapped in a single DB transaction. Failure
 * rolls back; the audit log is appended only when the transaction
 * commits successfully.
 */
class AnalyticsResetService
{
    /** Allowed scope keywords accepted by the public API. */
    public const SCOPE_ALL        = 'all';
    public const SCOPE_PRODUCTS   = 'products';
    public const SCOPE_CATEGORIES = 'categories';
    public const SCOPE_COUPONS    = 'coupons';
    public const SCOPE_SENDERS    = 'senders';

    public const VALID_SCOPES = [
        self::SCOPE_ALL,
        self::SCOPE_PRODUCTS,
        self::SCOPE_CATEGORIES,
        self::SCOPE_COUPONS,
        self::SCOPE_SENDERS,
    ];

    /**
     * @return array{affected: int, before: array, after: array, scope: string}
     */
    public function reset(string $scope): array
    {
        if (!in_array($scope, self::VALID_SCOPES, true)) {
            throw new \InvalidArgumentException('Unknown scope: ' . $scope);
        }

        $before = $this->collectTotals();
        $affected = 0;

        DB::transaction(function () use ($scope, &$affected) {
            if ($scope === self::SCOPE_ALL || $scope === self::SCOPE_PRODUCTS) {
                $affected += DB::table('products')->update([
                    'count_views' => 0,
                    'count_sales' => 0,
                    'count_all'   => 0,
                ]);
            }
            if ($scope === self::SCOPE_ALL || $scope === self::SCOPE_CATEGORIES) {
                $affected += DB::table('categories')->update(['count_views' => 0]);
            }
            if ($scope === self::SCOPE_ALL || $scope === self::SCOPE_COUPONS) {
                if ($this->columnExists('coupons', 'count_uses_now')) {
                    $affected += DB::table('coupons')->update(['count_uses_now' => 0]);
                }
                if ($this->tableExists('coupons_uses')) {
                    $affected += DB::table('coupons_uses')->delete();
                }
            }
            if ($scope === self::SCOPE_ALL || $scope === self::SCOPE_SENDERS) {
                if ($this->tableExists('senders')) {
                    $affected += DB::table('senders')->update([
                        'count_all'     => 0,
                        'count_success' => 0,
                        'count_fail'    => 0,
                    ]);
                }
            }
        });

        return [
            'scope'    => $scope,
            'affected' => $affected,
            'before'   => $before,
            'after'    => $this->collectTotals(),
        ];
    }

    /**
     * Aggregate totals for the audit snapshot. Cheap: simple SUMs.
     */
    public function collectTotals(): array
    {
        $totals = [
            'products' => [
                'count_views' => (int) DB::table('products')->sum('count_views'),
                'count_sales' => (int) DB::table('products')->sum('count_sales'),
                'count_all'   => (int) DB::table('products')->sum('count_all'),
            ],
            'categories' => [
                'count_views' => (int) DB::table('categories')->sum('count_views'),
            ],
        ];
        if ($this->tableExists('senders')) {
            $totals['senders'] = [
                'count_all'     => (int) DB::table('senders')->sum('count_all'),
                'count_success' => (int) DB::table('senders')->sum('count_success'),
                'count_fail'    => (int) DB::table('senders')->sum('count_fail'),
            ];
        }
        if ($this->tableExists('coupons_uses')) {
            $totals['coupon_uses'] = (int) DB::table('coupons_uses')->count();
        }
        return $totals;
    }

    private function tableExists(string $name): bool
    {
        try {
            return \Schema::hasTable($name);
        } catch (\Throwable $e) {
            Log::warning('AnalyticsResetService: hasTable check failed', ['table' => $name]);
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return \Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
