<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Models\Material;
use App\Models\Product;
use App\Models\Tariff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Материалы';
    protected static ?string $modelLabel = 'материал';
    protected static ?string $pluralModelLabel = 'материалы';
    protected static ?int $navigationSort = 12;

    public const STATUS_OPTIONS = [
        1 => 'Доступен',
        2 => 'Продан',
        3 => 'Отключён',
        4 => 'Зарезервирован',
    ];

    /**
     * Statuses shown on the warehouse view. Sold (2) materials are
     * tracked inside their order and are intentionally excluded here.
     */
    public const WAREHOUSE_STATUSES = [1, 3, 4];

    public const WAREHOUSE_STATUS_OPTIONS = [
        1 => 'Доступен',
        3 => 'Отключён',
        4 => 'Зарезервирован',
    ];

    public static function form(Form $form): Form
    {
        // Форма повторяет модалку #addMaterial из старой админки:
        // pid → tid (зависит от pid) → body (по одному в строчку) → опциональный .txt.
        return $form->schema([
            Forms\Components\Select::make('pid')
                ->label('Товар')
                ->options(fn () => Product::orderBy('id', 'desc')->limit(1000)->pluck('title', 'id')->all())
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Forms\Set $set) => $set('tid', null))
                ->columnSpanFull(),

            Forms\Components\Select::make('tid')
                ->label('Тариф')
                ->options(function (Forms\Get $get): array {
                    $pid = (int) $get('pid');
                    if ($pid <= 0) {
                        return [];
                    }
                    return Tariff::where('pid', $pid)
                        ->orderBy('sort')
                        ->get()
                        ->mapWithKeys(fn (Tariff $t) => [
                            $t->id => Tariff::num_decline((int) $t->title, ['день', 'дня', 'дней']) . ' — ' . $t->price,
                        ])
                        ->all();
                })
                ->required()
                ->live()
                ->columnSpanFull(),

            Forms\Components\Textarea::make('body')
                ->label('Содержимое')
                ->rows(8)
                ->placeholder('Вставьте материал (один в строчку — каждая строка станет отдельным материалом)')
                ->required(fn (Forms\Get $get) => empty($get('file_upload')))
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('file_upload')
                ->label('Файл (.txt)')
                ->disk('local')
                ->directory('tmp/material-uploads')
                ->preserveFilenames()
                ->acceptedFileTypes(['text/plain'])
                ->maxSize(20_000)
                ->helperText('Опционально. Каждая строка из файла станет отдельным материалом.')
                ->dehydrated(true)
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->label('Статус')
                ->options(self::STATUS_OPTIONS)
                ->default(1)
                ->required()
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereIn('status', self::WAREHOUSE_STATUSES))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('pid')
                    ->label('Товар')
                    ->formatStateUsing(fn ($state) => Product::where('id', $state)->value('title') ?? '#' . $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('tid')
                    ->label('Срок')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state, Material $r) => self::tariffLabel((int) $r->tid, (int) $r->pid))
                    ->sortable(query: function (\Illuminate\Database\Eloquent\Builder $q, string $direction) {
                        $q->orderBy('tid', $direction);
                    }),
                Tables\Columns\TextColumn::make('body')->label('Содержимое')->limit(60),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn ($state) => self::STATUS_OPTIONS[$state] ?? $state)
                    ->colors(['success' => 1, 'secondary' => 2, 'danger' => 3, 'warning' => 4]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->formatStateUsing(fn ($state) => $state ? date('Y-m-d H:i', (int) $state) : '—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::WAREHOUSE_STATUS_OPTIONS),
                Tables\Filters\SelectFilter::make('pid')
                    ->label('Товар')
                    ->options(fn () => Product::orderBy('id', 'desc')->limit(500)->pluck('title', 'id')->all())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('tid')
                    ->label('Срок (тариф)')
                    ->options(function (): array {
                        // Group all tariffs by their day-count title so the
                        // filter is meaningful across products. Distinct
                        // titles → choose by days, not by tariff id.
                        $opts = [];
                        $tariffs = Tariff::query()
                            ->select(['id', 'title'])
                            ->get();
                        foreach ($tariffs as $t) {
                            $opts[$t->id] = Tariff::num_decline((int) $t->title, ['день', 'дня', 'дней']) . ' (#' . $t->id . ')';
                        }
                        // Order by parsed integer days.
                        uasort($opts, static function ($a, $b) {
                            preg_match('/^(\d+)/', $a, $ma);
                            preg_match('/^(\d+)/', $b, $mb);
                            return (int) ($ma[1] ?? 0) <=> (int) ($mb[1] ?? 0);
                        });
                        return $opts;
                    })
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageMaterials::route('/')];
    }

    private static function tariffLabel(int $tid, int $pid): string
    {
        if ($tid <= 0) {
            return '—';
        }
        $tariff = Tariff::where('id', $tid)->where('pid', $pid)->first();
        return $tariff
            ? Tariff::num_decline((int) $tariff->title, ['день', 'дня', 'дней'])
            : '#' . $tid;
    }
}
