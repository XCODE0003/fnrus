<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramChannelResource\Pages;
use App\Models\Shop;
use App\Models\TelegramChannel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class TelegramChannelResource extends Resource
{
    protected static ?string $model = TelegramChannel::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Telegram-каналы';
    protected static ?string $modelLabel = 'канал';
    protected static ?string $pluralModelLabel = 'каналы';
    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Название')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('chat_id')
                ->label('Chat ID / @username')
                ->required()
                ->maxLength(64)
                ->helperText('Например: @my_channel или -1001234567890'),

            Forms\Components\Select::make('type')
                ->label('Тип')
                ->options([
                    'channel' => 'Канал',
                    'group' => 'Группа',
                ])
                ->required()
                ->default('channel'),

            Forms\Components\Toggle::make('is_active')
                ->label('Активен')
                ->default(true),

            Forms\Components\TextInput::make('sort_order')
                ->label('Сортировка')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('chat_id')->label('Chat')->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors(['primary' => 'channel', 'success' => 'group']),
                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('№')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Активен'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTelegramChannels::route('/'),
        ];
    }
}
