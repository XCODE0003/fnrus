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
            'token' => $shop?->token,
            'username' => $shop?->username,
        ]);
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
        $shop->fill([
            'token' => $data['token'] ?? '',
            'username' => $data['username'] ?? '',
            'updated_at' => time(),
        ])->save();
        Notification::make()->success()->title('Сохранено')->send();
    }
}
