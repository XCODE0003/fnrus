<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LinkAdResource\Pages;
use App\Models\LinkAd;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class LinkAdResource extends Resource
{
    protected static ?string $model = LinkAd::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Маркетинг';
    protected static ?string $navigationLabel = 'Реф-ссылки';
    protected static ?string $modelLabel = 'ссылка';
    protected static ?string $pluralModelLabel = 'реф-ссылки';
    protected static ?int $navigationSort = 52;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(255),
            Forms\Components\TextInput::make('code')->label('Код')->required()->maxLength(64)->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('code')->label('Код')->searchable(),
                Tables\Columns\TextColumn::make('visits_total')->label('Всего')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('visits_unique')->label('Уник.')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->formatStateUsing(fn ($state) => $state ? date('Y-m-d', (int) $state) : '—'),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageLinkAds::route('/')];
    }
}
