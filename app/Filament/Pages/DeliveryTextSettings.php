<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class DeliveryTextSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Текст после оплаты';
    protected static ?string $title = 'Текст на странице после оплаты';
    protected static ?int $navigationSort = 77;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/delivery-text';

    protected function settingsFields(): array
    {
        return ['delivery_text_ru', 'delivery_text_en'];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('hint')
                ->label('')
                ->content('Этот текст показывается на странице доставки ключа после оплаты (блок «Внимание»).'),

            Forms\Components\Section::make('Русский')->schema([
                Forms\Components\RichEditor::make('delivery_text_ru')
                    ->label('')
                    ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList'])
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('English')->schema([
                Forms\Components\RichEditor::make('delivery_text_en')
                    ->label('')
                    ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList'])
                    ->columnSpanFull(),
            ])->collapsible()->collapsed(true),
        ])->statePath('data');
    }
}
