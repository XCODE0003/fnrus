<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class TermsOfUseSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Условия пользования';
    protected static ?string $title = 'Условия пользования';
    protected static ?int $navigationSort = 77;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/terms';

    protected function settingsFields(): array
    {
        return ['terms_content_ru', 'terms_content_en'];
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
                ->content('Текст страницы https://<сайт>/terms. Если поле пустое — показывается текст по умолчанию из языковых файлов.'),
            Forms\Components\Section::make('Русский')->schema([
                Forms\Components\RichEditor::make('terms_content_ru')
                    ->label('')
                    ->toolbarButtons($toolbar)
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('covers')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('English')->schema([
                Forms\Components\RichEditor::make('terms_content_en')
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
