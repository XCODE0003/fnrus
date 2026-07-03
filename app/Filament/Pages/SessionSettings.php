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
    protected static ?string $title = 'Сроки: сессия пользователей и 2FA';
    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/session';

    protected function settingsFields(): array
    {
        return ['session_ttl_days', 'two_factor_ttl_hours'];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Сессия пользователей')
                ->description('Сколько дней пользователь остаётся в аккаунте без повторного входа. По истечении срока сессия сбрасывается. Применяется к сессиям, начатым после сохранения.')
                ->schema([
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
                ]),

            Forms\Components\Section::make('Двухфакторная аутентификация (админка)')
                ->description('Как часто в админке снова спрашивать код 2FA. После успешного ввода кода 2FA не запрашивается в течение указанного срока.')
                ->schema([
                    Forms\Components\TextInput::make('two_factor_ttl_hours')
                        ->label('Повторный запрос 2FA')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(720)
                        ->default(12)
                        ->suffix('часов')
                        ->helperText('От 1 до 720 часов (30 дней). По умолчанию 12.')
                        ->columnSpan(1),
                ]),
        ])->statePath('data');
    }
}
