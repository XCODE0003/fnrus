<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class PrivacyPolicySettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Политика конфиденциальности';
    protected static ?string $title = 'Политика конфиденциальности';
    protected static ?int $navigationSort = 76;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/privacy';

    protected function settingsFields(): array
    {
        return ['privacy_content_ru', 'privacy_content_en'];
    }

    public function form(Form $form): Form
    {
        $toolbar = [
            'attachFiles', 'blockquote', 'bold', 'bulletList',
            'codeBlock', 'h2', 'h3', 'italic', 'link',
            'orderedList', 'strike', 'underline', 'redo', 'undo',
        ];

        return $form->schema([
            Forms\Components\Placeholder::make('hint')
                ->label('')
                ->content('Текст страницы https://<сайт>/privacy. Если поле пустое — показывается текст по умолчанию из языковых файлов.'),
            Forms\Components\Section::make('Русский')->schema([
                Forms\Components\RichEditor::make('privacy_content_ru')
                    ->label('')
                    ->toolbarButtons($toolbar)
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('covers')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('English')->schema([
                Forms\Components\RichEditor::make('privacy_content_en')
                    ->label('')
                    ->toolbarButtons($toolbar)
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('covers')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
            ])->collapsible()->collapsed(true),
        ])->statePath('data');
    }
}
