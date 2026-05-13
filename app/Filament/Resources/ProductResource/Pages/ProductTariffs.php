<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\MaterialResource;
use App\Filament\Resources\ProductResource;
use App\Models\Material;
use App\Models\Product;
use App\Models\Tariff;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Level 2 page in the product → tariff → material drill-down.
 *
 * Displays every Tariff (срок) that belongs to the currently chosen
 * product with live counts of available / sold / total materials.
 * Each row exposes a "Управление ключами" action that opens the
 * Materials list pre-filtered by pid + tid (level 3).
 */
class ProductTariffs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ProductResource::class;

    protected static string $view = 'filament.resources.product.tariffs';

    protected static ?string $title = 'Сроки и ключи';

    public ?Product $record = null;

    public function mount($record = null): void
    {
        // SubstituteBindings may pass the Product instance; sometimes the
        // raw id arrives instead. Handle both.
        if ($record instanceof Product) {
            $this->record = $record;
            return;
        }
        $id = is_numeric($record) ? (int) $record : 0;
        $this->record = ProductResource::resolveRecordRouteBinding($id)
            ?? Product::query()->where('id', $id)->first();
        abort_if($this->record === null, 404);
    }

    public function getTitle(): string
    {
        return 'Сроки: ' . ($this->record->title ?? '#' . $this->record->id);
    }

    public function getBreadcrumb(): string
    {
        return 'Сроки';
    }

    public function getHeading(): string
    {
        return 'Сроки: ' . ($this->record->title ?? '#' . $this->record->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Назад к товарам')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ProductResource::getUrl('index')),
            Actions\Action::make('editProduct')
                ->label('Редактировать товар')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn (): string => ProductResource::getUrl('edit', ['record' => $this->record->id])),
            Actions\Action::make('addMaterials')
                ->label('Добавить ключи')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => MaterialResource::getUrl('index', [
                    'tableFilters[pid][value]' => $this->record->id,
                ])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Tariff::query()
                ->where('pid', $this->record->id)
                ->orderByRaw('CAST(title AS UNSIGNED) ASC'))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Срок')
                    ->formatStateUsing(fn ($state) => Tariff::num_decline((int) $state, ['день', 'дня', 'дней']))
                    ->badge()
                    ->color('info')
                    ->sortable(query: fn (Builder $q, string $direction) => $q->orderByRaw('CAST(title AS UNSIGNED) ' . $direction)),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ' ') . ' ₽'),
                Tables\Columns\TextColumn::make('available_count')
                    ->label('Доступно')
                    ->state(fn (Tariff $r): int => Material::where('pid', $this->record->id)->where('tid', $r->id)->where('status', 1)->count())
                    ->badge()
                    ->color(fn ($state) => (int) $state <= 0 ? 'danger' : ((int) $state < 5 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('sold_count')
                    ->label('Продано')
                    ->state(fn (Tariff $r): int => Material::where('pid', $this->record->id)->where('tid', $r->id)->where('status', 2)->count())
                    ->color('gray'),
                Tables\Columns\TextColumn::make('reserved_count')
                    ->label('Резерв')
                    ->state(fn (Tariff $r): int => Material::where('pid', $this->record->id)->where('tid', $r->id)->where('status', 4)->count())
                    ->color('warning'),
                Tables\Columns\TextColumn::make('total_count')
                    ->label('Всего')
                    ->state(fn (Tariff $r): int => Material::where('pid', $this->record->id)->where('tid', $r->id)->count()),
            ])
            ->actions([
                Tables\Actions\Action::make('manageKeys')
                    ->label('Управление ключами')
                    ->icon('heroicon-o-key')
                    ->color('primary')
                    ->url(fn (Tariff $r): string => MaterialResource::getUrl('index', [
                        'tableFilters[pid][value]' => $this->record->id,
                        'tableFilters[tid][value]' => $r->id,
                    ])),
            ])
            ->paginated(false);
    }
}
