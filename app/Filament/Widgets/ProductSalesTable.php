<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Services\DashboardStatsService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class ProductSalesTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Продажи по товарам';

    public function table(Table $table): Table
    {
        /** @var DashboardStatsService $svc */
        $svc = app(DashboardStatsService::class);
        [$start, $end] = $svc->range($this->filters['from'] ?? null, $this->filters['to'] ?? null);
        $currency = $svc->currencySymbol();
        $shopId = (int) (Shop::getDefault()?->id ?? 0);

        // Laravel auto-prefixes table names AND join-aliases with the configured
        // prefix (here: "new_"). leftJoinSub('oa', ...) becomes "new_oa" in SQL,
        // so column references must use the prefixed alias too.
        $prefix = DB::connection()->getTablePrefix();
        $aliasRaw = $prefix . 'oa';

        // Subquery: per-product aggregates inside the date range.
        // Built via DB::table('orders') so the inner FROM gets the prefix once.
        $aggregateSub = DB::table('orders')
            ->selectRaw('pid, COUNT(*) AS range_sales, COALESCE(SUM(amount), 0) AS range_profits')
            ->where('sid', $shopId)
            ->where('status', 2)
            ->whereBetween('payment_at', [$start, $end])
            ->groupBy('pid');

        return $table
            ->query(
                Product::query()
                    ->when($shopId > 0, fn (Builder $q) => $q->where('sid', $shopId))
                    ->leftJoinSub($aggregateSub, 'oa', fn (JoinClause $j) => $j->on('products.id', '=', 'oa.pid'))
                    ->select([
                        'products.id',
                        'products.title',
                        'products.cid',
                        'products.price',
                        'products.currency',
                        'products.count_views',
                        'products.count_sales',
                        DB::raw("COALESCE({$aliasRaw}.range_sales, 0) AS range_sales"),
                        DB::raw("COALESCE({$aliasRaw}.range_profits, 0) AS range_profits"),
                    ])
            )
            ->defaultSort('range_profits', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('title')
                    ->label('Товар')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cid')
                    ->label('Категория')
                    ->formatStateUsing(fn ($state) => Category::query()->where('id', $state)->value('title') ?? '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('range_sales')
                    ->label('Заказов в периоде')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('range_profits')
                    ->label('Сумма в периоде')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ' ') . $currency),

                Tables\Columns\TextColumn::make('count_sales')
                    ->label('Продаж всего')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('count_views')
                    ->label('Просмотров')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2, '.', ' ') . ' ' . ($record->currency ?? ''))
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cid')
                    ->label('Категория')
                    ->options(fn () => Category::query()
                        ->where('cid', 0)
                        ->where('visibility', 1)
                        ->orderBy('sort')
                        ->pluck('title', 'id')
                        ->all())
                    ->searchable(),

                Tables\Filters\Filter::make('only_with_sales')
                    ->label('Только с продажами в периоде')
                    ->query(fn (Builder $q) => $q->where("{$aliasRaw}.range_sales", '>', 0))
                    ->toggle(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
