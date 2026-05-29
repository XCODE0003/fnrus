<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AboutItemResource\Pages;
use App\Models\AboutItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class AboutItemResource extends Resource
{
    protected static ?string $model = AboutItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Кнопки "О нас"';
    protected static ?string $modelLabel = 'кнопка "О нас"';
    protected static ?string $pluralModelLabel = 'кнопки "О нас"';
    protected static ?int $navigationSort = 15;

    public const ICON_OPTIONS = [
        'telegram'  => 'Telegram',
        'discord'   => 'Discord',
        'vk'        => 'ВКонтакте',
        'youtube'   => 'YouTube',
        'instagram' => 'Instagram',
        'star'      => 'Звезда',
        'link'      => 'Ссылка',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('icon')
                ->label('Иконка')
                ->options(self::ICON_OPTIONS)
                ->default('link')
                ->required()
                ->helperText('Тип иконки соц. сети или ссылки.'),

            Forms\Components\TextInput::make('label_ru')
                ->label('Подпись (ru)')
                ->required()
                ->maxLength(120)
                ->placeholder('Telegram канал'),

            Forms\Components\TextInput::make('label_en')
                ->label('Подпись (en)')
                ->maxLength(120)
                ->placeholder('Telegram channel')
                ->helperText('Опционально — если пусто, используется русская подпись.'),

            Forms\Components\TextInput::make('url_text')
                ->label('Текст кнопки')
                ->maxLength(120)
                ->placeholder('Перейти'),

            Forms\Components\TextInput::make('url')
                ->label('Ссылка')
                ->required()
                ->url()
                ->maxLength(2048)
                ->placeholder('https://t.me/your_channel'),

            Forms\Components\TextInput::make('sort_order')
                ->label('Порядок')
                ->numeric()
                ->default(0)
                ->helperText('Меньше — выше в списке. Можно перетаскивать строки в таблице.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\BadgeColumn::make('icon')
                    ->label('Иконка')
                    ->formatStateUsing(fn ($state) => self::ICON_OPTIONS[$state] ?? $state),
                Tables\Columns\TextColumn::make('label_ru')->label('Подпись (ru)')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('label_en')->label('Подпись (en)')->limit(60),
                Tables\Columns\TextColumn::make('url_text')->label('Текст кнопки')->limit(40),
                Tables\Columns\TextColumn::make('url')->label('Ссылка')->limit(50)->url(fn ($state) => $state, true),
                Tables\Columns\TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAboutItems::route('/')];
    }
}
