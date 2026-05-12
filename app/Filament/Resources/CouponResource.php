<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Маркетинг';
    protected static ?string $navigationLabel = 'Промокоды';
    protected static ?string $modelLabel = 'промокод';
    protected static ?string $pluralModelLabel = 'промокоды';
    protected static ?int $navigationSort = 51;

    public const SALE_TYPE_PERCENT = 0;
    public const SALE_TYPE_FIXED = 1;

    public const SALE_TYPE_OPTIONS = [
        self::SALE_TYPE_PERCENT => 'процентов',
        self::SALE_TYPE_FIXED => 'долларов',
    ];

    public const COUNT_USES_TYPE_OPTIONS = [
        0 => 'штук',
        1 => 'долларов',
    ];

    public static function form(Form $form): Form
    {
        // Полная копия модалки #newCoupon из products/coupons.blade.php.
        return $form->schema([
            Forms\Components\Select::make('gids')
                ->label('Товары')
                ->multiple()
                ->options(fn () => Product::orderBy('id', 'desc')->limit(1000)->pluck('title', 'id')->all())
                ->searchable()
                ->required()
                ->afterStateHydrated(function (Forms\Components\Select $component, $state): void {
                    if (is_string($state) && $state !== '') {
                        $decoded = json_decode($state, true);
                        if (is_array($decoded)) {
                            $component->state(array_values($decoded));
                        }
                    }
                })
                ->dehydrateStateUsing(fn ($state) => json_encode(array_values((array) $state), JSON_UNESCAPED_UNICODE))
                ->columnSpanFull(),

            Forms\Components\TextInput::make('code')
                ->label('Код купона')
                ->required()
                ->minLength(3)
                ->maxLength(50)
                ->placeholder('Введите код купона')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('sale')
                ->label('Скидка')
                ->numeric()
                ->required()
                ->placeholder('Укажите число'),

            Forms\Components\Select::make('sale_type')
                ->label('Тип скидки')
                ->options(self::SALE_TYPE_OPTIONS)
                ->default(self::SALE_TYPE_PERCENT)
                ->required(),

            Forms\Components\TextInput::make('min_sum')
                ->label('Минимальная сумма')
                ->numeric()
                ->minValue(0)
                ->maxValue(100000)
                ->default(0)
                ->required()
                ->placeholder('Введите число'),

            Forms\Components\TextInput::make('count_uses_min')
                ->label('Минимальный лимит')
                ->numeric()
                ->minValue(1)
                ->maxValue(10000)
                ->default(1)
                ->required()
                ->placeholder('Укажите число'),

            Forms\Components\Select::make('count_uses_type')
                ->label('Единица лимита')
                ->options(self::COUNT_USES_TYPE_OPTIONS)
                ->default(0)
                ->required(),

            Forms\Components\TextInput::make('count_uses_max')
                ->label('Лимит использований (0 = без лимита)')
                ->numeric()
                ->minValue(0)
                ->maxValue(10000)
                ->default(0)
                ->required()
                ->placeholder('Введите число'),

            Forms\Components\Toggle::make('is_new_users')
                ->label('Только для новых пользователей'),

            Forms\Components\Toggle::make('is_one_time')
                ->label('Единоразовое использование'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('code')->label('Код')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sale')
                    ->label('Скидка')
                    ->formatStateUsing(fn ($state, Coupon $r) => (int) $r->sale_type === self::SALE_TYPE_PERCENT ? "{$state}%" : "{$state}\$"),
                Tables\Columns\TextColumn::make('min_sum')->label('Мин. сумма'),
                Tables\Columns\TextColumn::make('count_uses_max')->label('Лимит')
                    ->formatStateUsing(fn ($state) => ((int) $state === 0) ? '∞' : (string) $state),
                Tables\Columns\IconColumn::make('is_new_users')->label('Новые')->boolean(),
                Tables\Columns\IconColumn::make('is_one_time')->label('1-раз')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_new_users')->label('Только новые'),
                Tables\Filters\TernaryFilter::make('is_one_time')->label('Одноразовые'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(static function (array $data): array {
                        $data['updated_at'] = time();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCoupons::route('/'),
        ];
    }
}
