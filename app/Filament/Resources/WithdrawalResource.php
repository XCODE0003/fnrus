<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Финансы';
    protected static ?string $navigationLabel = 'Выводы';
    protected static ?string $modelLabel = 'вывод';
    protected static ?string $pluralModelLabel = 'выводы';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('user_id')->label('User ID')->numeric()->disabled(),
            Forms\Components\TextInput::make('first_name')->label('Имя')->disabled(),
            Forms\Components\TextInput::make('card_number')->label('Реквизиты')->disabled(),
            Forms\Components\TextInput::make('sum')->label('Сумма')->numeric()->disabled(),
            Forms\Components\Select::make('status')
                ->label('Статус')
                ->options([
                    1 => 'Создан',
                    2 => 'В обработке',
                    3 => 'Выплачен',
                    4 => 'Отклонён',
                ])
                ->required(),
            Forms\Components\Select::make('method')
                ->label('Метод')
                ->options([1 => 'Карта', 2 => 'СБП', 3 => 'Авто', 4 => 'Крипто'])
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('Пользователь')
                    ->formatStateUsing(fn ($state) => $state ? (User::where('id', $state)->value('email') ?: '#' . $state) : '—'),
                Tables\Columns\TextColumn::make('first_name')->label('Имя')->searchable(),
                Tables\Columns\TextColumn::make('card_number')->label('Реквизиты')->limit(24),
                Tables\Columns\TextColumn::make('sum')->label('Сумма')->numeric(2)->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn ($state) => [1 => 'Создан', 2 => 'Обработка', 3 => 'Выплачен', 4 => 'Отклонён'][$state] ?? $state)
                    ->colors(['secondary' => 1, 'warning' => 2, 'success' => 3, 'danger' => 4]),
                Tables\Columns\TextColumn::make('method')->label('Метод')
                    ->formatStateUsing(fn ($state) => [1 => 'Карта', 2 => 'СБП', 3 => 'Авто', 4 => 'Крипто'][$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([1 => 'Создан', 2 => 'Обработка', 3 => 'Выплачен', 4 => 'Отклонён']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
        return ['index' => Pages\ManageWithdrawals::route('/')];
    }
}
