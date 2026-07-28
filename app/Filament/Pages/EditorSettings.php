<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class EditorSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Загрузка в едиторе';
    protected static ?string $title = 'Лимит загрузки в RichEditor';
    protected static ?int $navigationSort = 80;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/editor';

    protected function settingsFields(): array
    {
        return ['editor_max_upload_mb'];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('hint')
                ->label('')
                ->content('Максимальный размер файла (изображения или видео), который можно загрузить кнопками «Картинка» и «Видео» в RichEditor.'),

            Forms\Components\TextInput::make('editor_max_upload_mb')
                ->label('Максимальный размер, MB')
                ->numeric()
                ->minValue(1)
                ->maxValue(500)
                ->default(100)
                ->required(),
        ])->statePath('data');
    }
}
