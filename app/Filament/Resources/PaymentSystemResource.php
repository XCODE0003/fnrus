<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentSystemResource\Pages;
use App\Models\PaymentSystem;
use App\Models\ShopSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\DB;

class PaymentSystemResource extends Resource
{
    protected static ?string $model = PaymentSystem::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Способы оплаты';
    protected static ?string $modelLabel = 'способ оплаты';
    protected static ?string $pluralModelLabel = 'способы оплаты';
    protected static ?int $navigationSort = 79;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(255),
            Forms\Components\TextInput::make('type')
                ->label('Системное имя (type)')
                ->required()
                ->maxLength(64)
                ->helperText('Стабильный alias, например: qiwi, card, crypto. Используется в коде.'),
            Forms\Components\TextInput::make('link')->label('Ссылка / endpoint')->maxLength(500),
            Forms\Components\TextInput::make('icon')->label('Иконка (slug или URL)')->maxLength(255),
            Forms\Components\Toggle::make('active')->label('Доступен в каталоге')->default(true)
                ->helperText('Если выключен — система оплаты вообще не работает на сайте, независимо от настроек магазина.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('paymentTimer')
                    ->label('Таймер оплаты')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->modalHeading('Время на оплату товара')
                    ->modalWidth('md')
                    ->fillForm(fn (): array => [
                        'booking_time' => (int) (ShopSettings::getDefault()?->booking_time ?? 20),
                    ])
                    ->form([
                        Forms\Components\TextInput::make('booking_time')
                            ->label('Минут на оплату')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1440)
                            ->required()
                            ->helperText('После создания заказа у покупателя есть это число минут на оплату. Истёкшие заказы переводятся в статус «Истёк срок».'),
                    ])
                    ->action(function (array $data): void {
                        $s = ShopSettings::getDefault();
                        if ($s) {
                            $s->booking_time = (int) $data['booking_time'];
                            $s->save();
                            Notification::make()->success()->title('Сохранено')->send();
                        } else {
                            Notification::make()->danger()->title('Настройки магазина не найдены')->send();
                        }
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Type')->searchable()->badge(),
                Tables\Columns\TextColumn::make('link')->label('Link')->limit(40),
                Tables\Columns\IconColumn::make('active')->label('Каталог')->boolean(),
                Tables\Columns\IconColumn::make('shop_active')
                    ->label('Подключён')
                    ->boolean()
                    ->state(fn (PaymentSystem $record): bool => self::shopMethod($record->id)?->active === 1),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->label('Доступен в каталоге'),
            ])
            ->actions([
                Tables\Actions\Action::make('credentials')
                    ->label('Токены')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading(fn (PaymentSystem $record) => 'Токены: ' . $record->title)
                    ->modalWidth('xl')
                    ->fillForm(function (PaymentSystem $record): array {
                        $row = self::shopMethod($record->id);
                        return [
                            'public_id'       => (string) ($row->public_id ?? ''),
                            'public_key'      => (string) ($row->public_key ?? ''),
                            'secret_key'      => (string) ($row->secret_key ?? ''),
                            'secret_key_two'  => (string) ($row->secret_key_two ?? ''),
                            'theme_code'      => (string) ($row->theme_code ?? ''),
                            'active'          => (int) ($row->active ?? 0) === 1,
                        ];
                    })
                    ->form(fn (PaymentSystem $record) => self::credentialsFormSchema($record))
                    ->action(function (PaymentSystem $record, array $data): void {
                        self::saveCredentials($record, $data);
                        Notification::make()->success()->title('Сохранено')->send();
                    }),
                Tables\Actions\EditAction::make()->label('Каталог'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('id', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentSystems::route('/'),
        ];
    }

    /** Default shop row for the per-shop payment-method config. */
    private static function defaultShopId(): int
    {
        $id = (int) DB::table('shops')->orderBy('id', 'asc')->value('id');
        return $id ?: 0;
    }

    /** @return object|null shops_payment_methods row for this catalog entry */
    private static function shopMethod(int $psid): ?object
    {
        $sid = self::defaultShopId();
        if ($sid <= 0) {
            return null;
        }
        return DB::table('shops_payment_methods')
            ->where('sid', $sid)
            ->where('psid', $psid)
            ->first();
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    private static function credentialsFormSchema(PaymentSystem $record): array
    {
        $type = (string) $record->type;
        $appUrl = rtrim((string) config('app.url'), '/');
        $callback = $appUrl . '/pay/callback/' . $type;

        return [
            Forms\Components\Placeholder::make('callback')
                ->label('Адрес для уведомлений (callback)')
                ->content($callback)
                ->helperText('Этот URL нужно вписать в кабинете провайдера как notify/result URL.'),

            Forms\Components\TextInput::make('public_id')
                ->label('ID кассы / Shop ID / Merchant ID / public_id')
                ->maxLength(255)
                ->helperText('Заполняйте только если ваша платёжная система это использует.'),

            Forms\Components\TextInput::make('public_key')
                ->label('Публичный ключ / API ключ (public_key)')
                ->maxLength(2000),

            Forms\Components\TextInput::make('secret_key')
                ->label('Секретный ключ / API-токен / Секретное слово #1 (secret_key)')
                ->maxLength(2000)
                ->password()
                ->revealable(),

            Forms\Components\TextInput::make('secret_key_two')
                ->label('Секретный ключ #2 / Salt / Курс звезды (secret_key_two)')
                ->maxLength(2000)
                ->password()
                ->revealable(),

            Forms\Components\TextInput::make('theme_code')
                ->label('Theme code / код виджета')
                ->maxLength(255),

            Forms\Components\Toggle::make('active')
                ->label('Подключён на сайте')
                ->helperText('Если выключен — этот метод не показывается покупателям, даже если токены заполнены.'),
        ];
    }

    private static function saveCredentials(PaymentSystem $record, array $data): void
    {
        $sid = self::defaultShopId();
        if ($sid <= 0) {
            return;
        }

        $values = [
            'public_id'      => (string) ($data['public_id'] ?? ''),
            'public_key'     => (string) ($data['public_key'] ?? ''),
            'secret_key'     => (string) ($data['secret_key'] ?? ''),
            'secret_key_two' => (string) ($data['secret_key_two'] ?? ''),
            'theme_code'     => (string) ($data['theme_code'] ?? ''),
            'active'         => (int) (! empty($data['active'])),
            'type'           => (string) $record->type,
            'updated_at'     => time(),
        ];

        $existing = self::shopMethod($record->id);
        if ($existing !== null) {
            DB::table('shops_payment_methods')
                ->where('sid', $sid)
                ->where('psid', $record->id)
                ->update($values);
        } else {
            DB::table('shops_payment_methods')->insert(array_merge($values, [
                'sid'  => $sid,
                'psid' => $record->id,
                'pid'  => 0,
            ]));
        }
    }
}
