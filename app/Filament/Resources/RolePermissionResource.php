<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RolePermissionResource\Pages;
use App\Models\Role;
use App\Models\RolePermission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class RolePermissionResource extends Resource
{
    protected static ?string $model = RolePermission::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Пользователи';
    protected static ?string $navigationLabel = 'Права ролей (raw)';
    protected static ?string $modelLabel = 'право';
    protected static ?string $pluralModelLabel = 'права ролей';
    protected static ?int $navigationSort = 61;
    // Основное управление — «Настройки → Управление ролями». Эту страницу
    // оставляем как «raw»-доступ к таблице role_permissions и прячем из меню.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('role_id')
                ->label('Роль')
                ->options(fn () => Role::orderBy('id')->pluck('title', 'id')->all())
                ->required(),
            Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(255),
            Forms\Components\TextInput::make('permission')->label('Permission key')->required()->maxLength(255),
            Forms\Components\Toggle::make('allow')->label('Разрешено')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('role_id')
                    ->label('Роль')
                    ->formatStateUsing(fn ($state) => Role::where('id', $state)->value('title') ?? '#' . $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('permission')->label('Permission')->searchable(),
                Tables\Columns\IconColumn::make('allow')->label('Разрешено')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Роль')
                    ->options(fn () => Role::orderBy('id')->pluck('title', 'id')->all()),
                Tables\Filters\TernaryFilter::make('allow')->label('Разрешено'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('role_id', 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageRolePermissions::route('/')];
    }
}
