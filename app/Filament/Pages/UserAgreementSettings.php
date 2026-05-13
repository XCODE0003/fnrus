<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class UserAgreementSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Пользовательское соглашение';
    protected static ?string $title = 'Пользовательское соглашение';
    protected static ?int $navigationSort = 76;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/policy';

    protected function settingsFields(): array
    {
        return ['policy_content_ru', 'policy_content_en'];
    }

    public function form(Form $form): Form
    {
        $toolbar = [
            'attachFiles', 'blockquote', 'bold', 'bulletList',
            'codeBlock', 'h2', 'h3', 'italic', 'link',
            'orderedList', 'strike', 'underline', 'redo', 'undo',
        ];

        return $form->schema([
            Forms\Components\Section::make('Русский')->schema([
                Forms\Components\RichEditor::make('policy_content_ru')
                    ->label('')
                    ->toolbarButtons($toolbar)
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('covers')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('English')->schema([
                Forms\Components\RichEditor::make('policy_content_en')
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
