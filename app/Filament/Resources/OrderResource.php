<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Member;
use App\Models\Order;
use App\Models\PaymentSystem;
use App\Models\Product;
use App\Models\ShopSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Продажи';
    protected static ?string $navigationLabel = 'Заказы';
    protected static ?string $modelLabel = 'заказ';
    protected static ?string $pluralModelLabel = 'заказы';
    protected static ?int $navigationSort = 1;

    private const STATUS_LABELS = [
        1 => 'В процессе',
        2 => 'Оплачен',
        3 => 'Отменен',
        4 => 'Истек срок',
    ];

    public static function form(Form $form): Form
    {
        $currency = self::currencySymbol();

        return $form->schema([
            Forms\Components\Section::make('Заказ')->schema([
                Forms\Components\TextInput::make('hash')->label('ID')->disabled(),
                Forms\Components\TextInput::make('title')->label('Товар')->disabled(),
                Forms\Components\TextInput::make('count_all')->label('Кол-во')->numeric()->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ' ') . $currency)
                    ->disabled(),
                Forms\Components\TextInput::make('method_payment')
                    ->label('Способ оплаты')
                    ->formatStateUsing(fn ($state) => self::paymentMethodLabel($state))
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options(self::STATUS_LABELS)
                    ->required(),
                Forms\Components\TextInput::make('created_at')
                    ->label('Создан')
                    ->formatStateUsing(fn ($state) => $state ? date('d.m.Y H:i', (int) $state) : '—')
                    ->disabled(),
                Forms\Components\TextInput::make('payment_at')
                    ->label('Оплачен')
                    ->formatStateUsing(fn ($state) => $state ? date('d.m.Y H:i', (int) $state) : '—')
                    ->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $currency = self::currencySymbol();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hash')
                    ->label('ID')
                    ->color('primary')
                    ->weight('bold')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Товар')
                    ->searchable()
                    ->wrap()
                    ->formatStateUsing(function ($state, Order $record): string {
                        $product = Product::query()->where('id', $record->pid)->first();
                        return $product?->title ?: ($state ?: '—');
                    }),

                Tables\Columns\TextColumn::make('bid')
                    ->label('Покупатель')
                    ->formatStateUsing(function ($state, Order $record): string {
                        $member = Member::query()
                            ->where('id', $state)
                            ->where('sid', $record->sid)
                            ->first();
                        return $member?->username ?: '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $memberIds = Member::query()
                            ->where(function ($q) use ($search) {
                                $q->where('username', 'like', "%{$search}%")
                                    ->orWhere('tid', $search);
                            })
                            ->pluck('id')
                            ->all();

                        return $query->where(function (Builder $q) use ($search, $memberIds) {
                            $q->where('hash', 'like', "%{$search}%")
                                ->orWhere('title', 'like', "%{$search}%");
                            if ($memberIds) {
                                $q->orWhereIn('bid', $memberIds);
                            }
                        });
                    }),

                Tables\Columns\TextColumn::make('method_payment')
                    ->label('Способ')
                    ->formatStateUsing(fn ($state) => self::paymentMethodLabel($state)),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ' ') . $currency),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->sortable()
                    ->formatStateUsing(function ($state, Order $record): string {
                        // Совпадает со старой админкой: для оплаченных показываем payment_at,
                        // для остальных — created_at.
                        $ts = ((int) $record->status === 2 && $record->payment_at)
                            ? (int) $record->payment_at
                            : (int) ($state ?? 0);
                        return $ts > 0 ? date('d.m.Y H:i', $ts) : '—';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::STATUS_LABELS[(int) $state] ?? '—')
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'warning',
                        2 => 'success',
                        3 => 'danger',
                        4 => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(self::STATUS_LABELS),

                SelectFilter::make('method_payment')
                    ->label('Способ оплаты')
                    ->options(fn () => self::paymentMethodOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100, 500, 1000])
            ->defaultPaginationPageOption(25);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    private static function currencySymbol(): string
    {
        return match (ShopSettings::getDefault()?->currency) {
            'USD' => '$',
            default => '₽',
        };
    }

    private static function paymentMethodLabel(?string $type): string
    {
        if ($type === null || $type === '') {
            return '—';
        }
        if ($type === 'bc') {
            return 'Баланс';
        }
        return PaymentSystem::query()->where('type', $type)->value('title') ?: $type;
    }

    /** @return array<string, string> */
    private static function paymentMethodOptions(): array
    {
        $options = ['bc' => 'Баланс'];
        $rows = PaymentSystem::query()->orderBy('title')->get(['type', 'title']);
        foreach ($rows as $row) {
            if ($row->type !== null && $row->type !== '') {
                $options[(string) $row->type] = (string) $row->title;
            }
        }
        return $options;
    }
}
