<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\ProductSalesTable;
use App\Filament\Widgets\SalesStatsOverview;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Аналитика';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = -1;

    public ?array $filters = null;

    public function mount(): void
    {
        $this->filters = [
            'from' => now()->subDays(30)->startOfDay()->toDateString(),
            'to' => now()->endOfDay()->toDateString(),
        ];

        $this->filtersForm->fill($this->filters);
    }

    protected function getForms(): array
    {
        return ['filtersForm'];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                    ])
                    ->extraAttributes(['class' => 'max-w-md'])
                    ->schema([
                        DatePicker::make('from')
                            ->label('С')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->closeOnDateSelection()
                            ->live()
                            ->maxDate(fn (callable $get) => $get('to') ?: now()),
                        DatePicker::make('to')
                            ->label('По')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->closeOnDateSelection()
                            ->live()
                            ->minDate(fn (callable $get) => $get('from'))
                            ->maxDate(now()),
                    ]),
            ])
            ->statePath('filters');
    }

    public function getWidgets(): array
    {
        return [
            SalesStatsOverview::class,
            ProductSalesTable::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
