<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SenderResource\Pages;
use App\Models\Sender;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SenderResource extends Resource
{
    protected static ?string $model = Sender::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Маркетинг';
    protected static ?string $navigationLabel = 'Telegram-рассылки';
    protected static ?string $modelLabel = 'рассылка';
    protected static ?string $pluralModelLabel = 'Telegram-рассылки';
    protected static ?int $navigationSort = 53;

    /** Совпадает со старой админкой 1-в-1. */
    private const STATUS_LABELS = [
        1 => 'Ожидает запуска',
        2 => 'Выполнено',
        3 => 'Черновик',
        4 => 'Выполняется',
        5 => 'Остановлено',
        6 => 'Магазин удален',
    ];

    private const TYPE_LABELS = [
        1 => 'Сообщение',
        2 => 'Репост',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Название')
                    ->placeholder('Напишите название')
                    ->minLength(4)
                    ->maxLength(255),

                Forms\Components\Select::make('type')
                    ->label('Тип')
                    ->options(self::TYPE_LABELS)
                    ->default(1)
                    ->required(),

                Forms\Components\RichEditor::make('message')
                    ->label('Описание')
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike',
                        'bulletList', 'orderedList', 'link', 'redo', 'undo',
                    ])
                    ->maxLength(4096)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('image')
                    ->label('Изображение')
                    ->image()
                    ->directory('senders')
                    ->disk('public')
                    ->maxSize(5120)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('buttons_data')
                    ->label('Кнопки')
                    ->schema([
                        Forms\Components\TextInput::make('text')->label('Текст')->required()->maxLength(64),
                        Forms\Components\TextInput::make('url')->label('URL')->url()->maxLength(255),
                    ])
                    ->columns(2)
                    ->addActionLabel('Добавить кнопку')
                    ->columnSpanFull()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\Repeater $component, ?Sender $record) {
                        if ($record && $record->buttons) {
                            $decoded = json_decode($record->buttons, true) ?: [];
                            $component->state(array_values($decoded));
                        }
                    }),

                Forms\Components\Toggle::make('disable_web_page_preview')
                    ->label('Отключить предпросмотр ссылок'),

                Forms\Components\Toggle::make('has_spoiler')
                    ->label('Включить спойлер на изображение'),
            ])->columns(2),

            Forms\Components\Section::make('Расписание')->schema([
                Forms\Components\Select::make('type_time')
                    ->label('Когда отправить')
                    ->options([0 => 'Сейчас', 1 => 'Позже'])
                    ->default(0)
                    ->live()
                    ->dehydrated(false),

                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('Время запуска')
                    ->seconds(false)
                    ->minDate(now())
                    ->displayFormat('d.m.Y H:i')
                    ->visible(fn (Forms\Get $get) => (int) $get('type_time') === 1)
                    ->required(fn (Forms\Get $get) => (int) $get('type_time') === 1)
                    ->dehydrated(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('icon')
                    ->label('')
                    ->state(fn () => true)
                    ->icon('heroicon-o-envelope')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::TYPE_LABELS[(int) $state] ?? '—')
                    ->color(fn ($state) => (int) $state === 2 ? 'info' : 'primary'),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Прогресс')
                    ->state(function (Sender $record): string {
                        $all = (int) ($record->count_all ?? 0);
                        $ok = (int) ($record->count_success ?? 0);
                        $fail = (int) ($record->count_fail ?? 0);
                        return $all . ' / ' . $ok . ' / ' . $fail;
                    })
                    ->tooltip('всего / успешно / неудачно'),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Дата запуска')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? date('d.m.Y H:i', (int) $state) : '—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::STATUS_LABELS[(int) $state] ?? '—')
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'warning',
                        2 => 'success',
                        3 => 'gray',
                        4 => 'primary',
                        5 => 'danger',
                        6 => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(self::STATUS_LABELS),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('started_at', 'desc')
            ->paginated([10, 25, 50, 100, 500, 1000])
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSenders::route('/'),
            'create' => Pages\CreateSender::route('/create'),
            'edit' => Pages\EditSender::route('/{record}/edit'),
        ];
    }
}
