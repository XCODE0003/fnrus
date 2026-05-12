<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopSettings;
use Illuminate\Support\Carbon;

/**
 * Compute dashboard analytics — date-range driven.
 * Mirrors StatController::get / OrderController::salesTop but is callable
 * from Filament widgets without going through the JWT-protected API.
 */
class DashboardStatsService
{
    /**
     * Resolve user-supplied {from, to} (date strings | null) into [startTs, endTs].
     * Defaults: last 30 days inclusive.
     *
     * @return array{0: int, 1: int}
     */
    public function range(?string $from, ?string $to): array
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

        if ($end->lt($start)) {
            $end = $start->copy()->endOfDay();
        }

        return [(int) $start->getTimestamp(), (int) $end->getTimestamp()];
    }

    public function currencySymbol(): string
    {
        return match (ShopSettings::getDefault()?->currency) {
            'USD' => '$',
            default => '₽',
        };
    }

    /**
     * @return array{sales: int, profits: float, members: int, currency: string}
     */
    public function totals(?string $from, ?string $to): array
    {
        $shopId = (int) (Shop::getDefault()?->id ?? 0);
        $currency = $this->currencySymbol();

        if ($shopId === 0) {
            return ['sales' => 0, 'profits' => 0.0, 'members' => 0, 'currency' => $currency];
        }

        [$start, $end] = $this->range($from, $to);

        $base = Order::query()
            ->where('status', 2)
            ->where('sid', $shopId)
            ->whereBetween('payment_at', [$start, $end]);

        return [
            'sales' => (int) (clone $base)->count(),
            'profits' => (float) (clone $base)->sum('amount'),
            'members' => (int) Member::query()
                ->where('is_active', 1)
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'currency' => $currency,
        ];
    }
}
