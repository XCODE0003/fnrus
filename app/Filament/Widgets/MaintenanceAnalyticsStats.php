<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\AnalyticsResetService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MaintenanceAnalyticsStats extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;

    /**
     * Human labels for nested keys returned by AnalyticsResetService::collectTotals.
     * Shape: ['scope' => ['metric' => 'human label']].
     */
    private const LABELS = [
        'products' => [
            'count_views' => 'Товары — просмотры',
            'count_sales' => 'Товары — продажи',
            'count_all' => 'Товары — всего',
        ],
        'categories' => [
            'count_views' => 'Категории — просмотры',
            'count_sales' => 'Категории — продажи',
            'count_all' => 'Категории — всего',
        ],
        'senders' => [
            'count_all' => 'Рассылки — всего',
            'count_success' => 'Рассылки — успешно',
            'count_fail' => 'Рассылки — ошибок',
        ],
        'coupons' => [
            'count_uses' => 'Промокоды — использований',
        ],
    ];

    private const COLORS = [
        'count_success' => 'success',
        'count_fail' => 'danger',
        'count_views' => 'info',
        'count_sales' => 'success',
    ];

    protected function getStats(): array
    {
        /** @var AnalyticsResetService $svc */
        $svc = app(AnalyticsResetService::class);

        $totals = $svc->collectTotals();
        if (empty($totals)) {
            return [];
        }

        $fmt = static fn ($v): string => is_numeric($v)
            ? number_format((float) $v, 0, '.', ' ')
            : (string) $v;

        $stats = [];

        foreach ($totals as $scope => $value) {
            // Flat numeric scalar: render as one card with the scope as label.
            if (! is_array($value)) {
                $label = self::scalarLabel($scope);
                $stats[] = Stat::make($label, $fmt($value))->color('gray');
                continue;
            }

            // Nested: each metric becomes its own card.
            foreach ($value as $metric => $metricValue) {
                $label = self::LABELS[$scope][$metric] ?? "{$scope} — {$metric}";
                $color = self::COLORS[$metric] ?? 'gray';
                $stats[] = Stat::make($label, $fmt($metricValue))->color($color);
            }
        }

        return $stats;
    }

    private static function scalarLabel(string $scope): string
    {
        return match ($scope) {
            'coupon_uses' => 'Использований промокодов',
            default => ucfirst(str_replace('_', ' ', $scope)),
        };
    }
}
