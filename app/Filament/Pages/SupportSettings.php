<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class SupportSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Техподдержка';
    protected static ?string $title = 'Окно техподдержки';
    protected static ?int $navigationSort = 78;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/support';

    protected function settingsFields(): array
    {
        return [
            'support_text',
            'support_btn1_text', 'support_btn1_url',
            'support_btn2_text', 'support_btn2_url',
            'support_btn3_text', 'support_btn3_url',
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('support_text')
                ->label('Текст сообщения')
                ->rows(3)
                ->placeholder('Если у вас возникли вопросы, обратитесь в поддержку:')
                ->columnSpanFull(),

            Forms\Components\Section::make('Кнопка 1')->schema([
                Forms\Components\TextInput::make('support_btn1_text')->label('Текст'),
                Forms\Components\TextInput::make('support_btn1_url')->label('Ссылка')->placeholder('https://t.me/...'),
            ])->columns(2),

            Forms\Components\Section::make('Кнопка 2 (необязательно)')->schema([
                Forms\Components\TextInput::make('support_btn2_text')->label('Текст'),
                Forms\Components\TextInput::make('support_btn2_url')->label('Ссылка'),
            ])->columns(2)->collapsible()->collapsed(true),

            Forms\Components\Section::make('Кнопка 3 (необязательно)')->schema([
                Forms\Components\TextInput::make('support_btn3_text')->label('Текст'),
                Forms\Components\TextInput::make('support_btn3_url')->label('Ссылка'),
            ])->columns(2)->collapsible()->collapsed(true),
        ])->statePath('data');
    }
}
