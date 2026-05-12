<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\SavesShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class SiteButtonsSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use SavesShopSettings;

    protected static ?string $navigationIcon = 'heroicon-o-cursor-arrow-rays';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Кнопки сайта';
    protected static ?string $title = 'Кнопки сайта';
    protected static ?int $navigationSort = 75;

    protected static string $view = 'filament.pages.settings-form';
    protected static ?string $slug = 'settings/site-buttons';

    public const ICONS = [
        'telegram' => 'Telegram',
        'discord' => 'Discord',
        'vk' => 'VK',
        'youtube' => 'YouTube',
        'instagram' => 'Instagram',
        'star' => 'Звезда',
        'link' => 'Ссылка',
    ];

    protected function settingsFields(): array
    {
        return [
            'btn_tg_bot_url', 'btn_tg_bot_text', 'btn_tg_bot_icon',
            'btn_buy_bot_url', 'btn_buy_bot_text', 'btn_buy_bot_icon',
            'btn_reviews_url', 'btn_reviews_text', 'btn_reviews_icon',
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Telegram Bot (подвал сайта)')->schema([
                Forms\Components\TextInput::make('btn_tg_bot_url')->label('Ссылка')->maxLength(255)->placeholder('https://t.me/...'),
                Forms\Components\TextInput::make('btn_tg_bot_text')->label('Текст')->maxLength(64),
                Forms\Components\Select::make('btn_tg_bot_icon')->label('Иконка')->options(self::ICONS)->default('telegram'),
            ])->columns(3),

            Forms\Components\Section::make('«Купить через бота» (главная)')->schema([
                Forms\Components\TextInput::make('btn_buy_bot_url')->label('Ссылка')->maxLength(255)->placeholder('https://t.me/...'),
                Forms\Components\TextInput::make('btn_buy_bot_text')->label('Текст')->maxLength(64),
                Forms\Components\Select::make('btn_buy_bot_icon')->label('Иконка')->options(self::ICONS)->default('telegram'),
            ])->columns(3),

            Forms\Components\Section::make('«Отзывы» (главная)')->schema([
                Forms\Components\TextInput::make('btn_reviews_url')->label('Ссылка')->maxLength(255)->placeholder('https://...'),
                Forms\Components\TextInput::make('btn_reviews_text')->label('Текст')->maxLength(64),
                Forms\Components\Select::make('btn_reviews_icon')->label('Иконка')->options(self::ICONS)->default('star'),
            ])->columns(3),
        ])->statePath('data');
    }
}
