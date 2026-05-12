<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceLogResource\Pages;
use App\Models\MaintenanceLog;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class MaintenanceLogResource extends Resource
{
    protected static ?string $model = MaintenanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Обслуживание';
    protected static ?string $navigationLabel = 'Журнал действий';
    protected static ?string $modelLabel = 'запись';
    protected static ?string $pluralModelLabel = 'журнал';
    protected static ?int $navigationSort = 91;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Когда')
                    ->formatStateUsing(fn ($state) => $state ? date('Y-m-d H:i:s', (int) $state) : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('admin_id')
                    ->label('Админ')
                    ->formatStateUsing(fn ($state) => $state ? (User::where('id', $state)->value('username') ?: '#' . $state) : '—'),
                Tables\Columns\BadgeColumn::make('action')->label('Действие')->searchable(),
                Tables\Columns\TextColumn::make('target')->label('Цель')->searchable(),
                Tables\Columns\TextColumn::make('affected')->label('Затронуто')->numeric(),
                Tables\Columns\TextColumn::make('ip')->label('IP'),
            ])
            ->filters([
                Tables\Filters\Filter::make('today')
                    ->label('За сегодня')
                    ->query(fn ($query) => $query->where('created_at', '>=', strtotime('today'))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Детали действия')
                    ->form([
                        \Filament\Forms\Components\KeyValue::make('details')
                            ->label('Детали')
                            ->disabled(),
                    ]),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMaintenanceLogs::route('/'),
        ];
    }
}
