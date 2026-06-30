<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

/**
 * Admin-editable storefront session length. Writes ShopSettings.session_ttl_days;
 * App\Providers\AppServiceProvider reads it to override the JWT TTL (and, through
 * it, the session_token cookie lifetime). Takes effect for sessions started
 * after the change.
 */
class SessionSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Срок сессии';
    protected static ?string $title = 'Срок сессии пользователей';
    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/session';

    protected function settingsFields(): array
    {
        return ['session_ttl_days'];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('hint')
                ->label('')
                ->content('Сколько дней пользователь остаётся в аккаунте без повторного входа. По истечении срока сессия сбрасывается и нужно войти заново. Применяется к сессиям, начатым после сохранения.'),

            Forms\Components\TextInput::make('session_ttl_days')
                ->label('Срок сессии')
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(365)
                ->default(30)
                ->suffix('дней')
                ->helperText('От 1 до 365 дней. Рекомендуется 30.')
                ->columnSpan(1),
        ])->statePath('data');
    }
}
