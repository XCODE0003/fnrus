<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\DashboardStatsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SalesStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $from = $this->filters['from'] ?? null;
        $to = $this->filters['to'] ?? null;

        /** @var DashboardStatsService $svc */
        $svc = app(DashboardStatsService::class);
        $totals = $svc->totals($from, $to);
        $rangeLabel = $this->rangeLabel($from, $to);

        return [
            Stat::make('Заработано', number_format($totals['profits'], 2, '.', ' ') . $totals['currency'])
                ->description($rangeLabel)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Продаж', number_format($totals['sales'], 0, '.', ' '))
                ->description($rangeLabel)
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Пользователей', number_format($totals['members'], 0, '.', ' '))
                ->description($rangeLabel)
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }

    private function rangeLabel(?string $from, ?string $to): string
    {
        $start = $from ? Carbon::parse($from)->format('d.m.Y') : '—';
        $end = $to ? Carbon::parse($to)->format('d.m.Y') : '—';
        return "{$start} — {$end}";
    }
}
