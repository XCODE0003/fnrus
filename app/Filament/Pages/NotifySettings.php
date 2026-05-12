<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class NotifySettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Уведомления';
    protected static ?string $title = 'Уведомления магазина';
    protected static ?int $navigationSort = 74;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/notify';

    protected function settingsFields(): array
    {
        return [
            'notify_target_id',
            'tg_notify_users',
            'tg_notify_buys',
            'tg_notify_balance',
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('notify_target_id')
                ->label('Chat ID получателя уведомлений')
                ->maxLength(64)
                ->placeholder('@username или -1001234567890')
                ->helperText('Чат, куда бот шлёт служебные уведомления. Бот должен быть в этом чате.'),

            Forms\Components\Section::make('Что отправлять')->schema([
                Forms\Components\Toggle::make('tg_notify_users')
                    ->label('Новые пользователи'),
                Forms\Components\Toggle::make('tg_notify_buys')
                    ->label('Новые покупки'),
                Forms\Components\Toggle::make('tg_notify_balance')
                    ->label('Пополнения баланса'),
            ])->columns(3),
        ])->statePath('data');
    }
}
