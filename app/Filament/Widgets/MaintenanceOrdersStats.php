<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MaintenanceOrdersStats extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $by = DB::table('orders')->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        $fmt = static fn (int $n): string => number_format($n, 0, '.', ' ');

        return [
            Stat::make('Заказы (оплачен)', $fmt((int) ($by[2] ?? 0)))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Заказы (просрочен)', $fmt((int) ($by[4] ?? 0)))
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),

            Stat::make('Заказы (отменён)', $fmt((int) ($by[3] ?? 0)))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Заказы (создан)', $fmt((int) ($by[1] ?? 0)))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),
        ];
    }
}
