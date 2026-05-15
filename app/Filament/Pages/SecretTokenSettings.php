<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Shop;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;

class SecretTokenSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Секретный токен';
    protected static ?string $title = 'Секретный токен Telegram-бота';
    protected static ?int $navigationSort = 71;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/token';

    public ?array $data = [];

    public function mount(): void
    {
        $shop = Shop::getDefault();
        $this->form->fill([
            // The column stores a Laravel-encrypted blob — decrypt for
            // display so the admin sees the actual @BotFather token.
            // Old rows that were saved in plaintext (regression of the
            // legacy form) will fail to decrypt; fall back to the raw
            // value so the admin can see what's in the column.
            'token' => self::decryptTokenSafe($shop?->token),
            'username' => $shop?->username,
        ]);
    }

    private static function decryptTokenSafe(?string $stored): string
    {
        if ($stored === null || $stored === '') {
            return '';
        }
        try {
            return (string) Crypt::decryptString($stored);
        } catch (\Throwable $e) {
            return $stored;
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('token')
                ->label('Токен бота')
                ->required()
                ->maxLength(255)
                ->placeholder('Вставьте сюда токен бота, выданный @botFather')
                ->helperText('Получить можно через @botFather в Telegram.'),

            Forms\Components\TextInput::make('username')
                ->label('Username бота')
                ->maxLength(255)
                ->placeholder('mybot'),
        ])->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $shop = Shop::getDefault();
        if ($shop === null) {
            Notification::make()->danger()->title('Магазин не настроен')->send();
            return;
        }
        $rawToken = trim((string) ($data['token'] ?? ''));
        $shop->fill([
            // The whole codebase (SenderController, BotController,
            // InvoiceController etc.) calls Crypt::decryptString on
            // shops.token — we MUST encrypt here, otherwise the bot
            // dies with "The payload is invalid".
            'token' => $rawToken === '' ? '' : Crypt::encryptString($rawToken),
            'username' => $data['username'] ?? '',
            'updated_at' => time(),
        ])->save();
        Notification::make()->success()->title('Сохранено')->send();
    }
}
