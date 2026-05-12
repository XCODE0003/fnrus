<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentSystemResource\Pages;
use App\Models\PaymentSystem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class PaymentSystemResource extends Resource
{
    protected static ?string $model = PaymentSystem::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Способы оплаты';
    protected static ?string $modelLabel = 'способ оплаты';
    protected static ?string $pluralModelLabel = 'способы оплаты';
    protected static ?int $navigationSort = 79;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(255),
            Forms\Components\TextInput::make('type')
                ->label('Системное имя (type)')
                ->required()
                ->maxLength(64)
                ->helperText('Стабильный alias, например: qiwi, card, crypto. Используется в коде.'),
            Forms\Components\TextInput::make('link')->label('Ссылка / endpoint')->maxLength(500),
            Forms\Components\TextInput::make('icon')->label('Иконка (slug или URL)')->maxLength(255),
            Forms\Components\Toggle::make('active')->label('Активен')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Type')->searchable()->badge(),
                Tables\Columns\TextColumn::make('link')->label('Link')->limit(40),
                Tables\Columns\IconColumn::make('active')->label('Активен')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->label('Активен'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('id', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentSystems::route('/'),
        ];
    }
}
