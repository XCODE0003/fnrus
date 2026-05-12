<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Пользователи';
    protected static ?string $navigationLabel = 'Пользователи';
    protected static ?string $modelLabel = 'пользователь';
    protected static ?string $pluralModelLabel = 'пользователи';
    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Учётные данные')->schema([
                Forms\Components\TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
                Forms\Components\TextInput::make('username')->label('Логин')->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Новый пароль')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->minLength(8)
                    ->helperText('Оставьте пустым, чтобы не менять'),
                Forms\Components\Select::make('role_id')
                    ->label('Роль')
                    ->options(fn () => Role::orderBy('id')->pluck('title', 'id')->all())
                    ->default(0),
            ])->columns(2),

            Forms\Components\Section::make('Доступ и флаги')->schema([
                Forms\Components\Toggle::make('is_active')->label('Активен')->default(true),
                Forms\Components\Toggle::make('is_ban')->label('Забанен'),
                Forms\Components\Toggle::make('email_notify_marketing')->label('Подписан на рассылки'),
                Forms\Components\Toggle::make('force_password_reset')->label('Требовать смену пароля'),
            ])->columns(2),

            Forms\Components\Section::make('Финансы (read-only)')->schema([
                Forms\Components\TextInput::make('balance_main')->label('Баланс')->numeric()->disabled(),
                Forms\Components\TextInput::make('balance_affiliate')->label('Реферальный')->numeric()->disabled(),
                Forms\Components\TextInput::make('ref_code')->label('Реф-код')->disabled(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('username')->label('Логин')->searchable(),
                Tables\Columns\BadgeColumn::make('role_id')
                    ->label('Роль')
                    ->formatStateUsing(fn ($state) => Role::where('id', $state)->value('title') ?? '#' . $state)
                    ->colors([
                        'success' => 1,
                        'warning' => 2,
                        'secondary' => 0,
                    ]),
                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
                Tables\Columns\IconColumn::make('is_ban')->label('Бан')->boolean()
                    ->trueColor('danger'),
                Tables\Columns\TextColumn::make('balance_main')->label('Баланс')->numeric(2),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Регистрация')
                    ->formatStateUsing(fn ($state) => $state ? date('Y-m-d', (int) $state) : '—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Роль')
                    ->options(fn () => Role::orderBy('id')->pluck('title', 'id')->all()),
                Tables\Filters\TernaryFilter::make('is_ban')->label('Забанен'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Активен'),
                Tables\Filters\TernaryFilter::make('email_notify_marketing')->label('Подписан на рассылку'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('forceReset')
                    ->label('Требовать смену пароля')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->forceFill(['force_password_reset' => 1])->save())
                    ->visible(fn (User $record) => ! (int) ($record->force_password_reset ?? 0)),
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
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
