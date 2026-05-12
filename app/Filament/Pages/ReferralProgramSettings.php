<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ReferralProgramSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Реф. программа';
    protected static ?string $title = 'Реферальная программа';
    protected static ?int $navigationSort = 72;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/referral';

    protected function settingsFields(): array
    {
        return ['ref_percent'];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('ref_percent')
                ->label('Процент реферального вознаграждения')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->required()
                ->helperText('Процент от платежей рефералов, который зачисляется пригласившему на реферальный баланс.'),
        ])->statePath('data');
    }
}
