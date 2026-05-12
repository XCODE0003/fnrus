<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class BalanceTopupSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Пополнение баланса';
    protected static ?string $title = 'Пополнение и вывод баланса';
    protected static ?int $navigationSort = 73;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/balance';

    protected function settingsFields(): array
    {
        return [
            'min_sum_topup',
            'min_sum_withdrawal_card',
            'min_sum_withdrawal_balance',
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Пополнение')->schema([
                Forms\Components\TextInput::make('min_sum_topup')
                    ->label('Минимальная сумма пополнения')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
            ]),

            Forms\Components\Section::make('Вывод')->schema([
                Forms\Components\TextInput::make('min_sum_withdrawal_card')
                    ->label('Мин. сумма для вывода на карту')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Forms\Components\TextInput::make('min_sum_withdrawal_balance')
                    ->label('Мин. сумма для вывода с реф. баланса')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
            ])->columns(2),
        ])->statePath('data');
    }
}
