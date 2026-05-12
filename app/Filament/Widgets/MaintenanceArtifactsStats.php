<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaintenanceArtifactsStats extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $fmt = static fn (int $n): string => number_format($n, 0, '.', ' ');

        $coupons = (int) DB::table('coupons')->count();
        $attachments = Schema::hasTable('attachments') ? (int) DB::table('attachments')->count() : 0;
        $exports = Schema::hasTable('materials_exports') ? (int) DB::table('materials_exports')->count() : 0;

        return [
            Stat::make('Промокоды', $fmt($coupons))
                ->descriptionIcon('heroicon-m-ticket')
                ->color('info'),

            Stat::make('Вложения', $fmt($attachments))
                ->descriptionIcon('heroicon-m-paper-clip')
                ->color('info'),

            Stat::make('История экспорта', $fmt($exports))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('info'),
        ];
    }
}
