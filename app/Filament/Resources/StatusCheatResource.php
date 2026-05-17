<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\StatusCheatResource\Pages;
use App\Jobs\BroadcastStatusChange;
use App\Models\Category;
use App\Models\ShopSettings;
use App\Models\StatusCheat;
use App\Services\TelegramPublisher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Log;

class StatusCheatResource extends Resource
{
    protected static ?string $model = StatusCheat::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Статусы софта';
    protected static ?string $modelLabel = 'статус';
    protected static ?string $pluralModelLabel = 'статусы';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Название чита')
                    ->required()
                    ->minLength(4)
                    ->maxLength(255),

                Forms\Components\Select::make('cid')
                    ->label('Игра')
                    ->options(fn () => Category::query()
                        ->where('cid', 0)
                        ->where('visibility', 1)
                        ->orderBy('sort')
                        ->pluck('title', 'id')
                        ->all())
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options(StatusCheat::STATUSES)
                    ->required()
                    ->default(1),
            ])->columns(3),

            Forms\Components\Section::make('Сообщение в Telegram')->schema([
                Forms\Components\Placeholder::make('placeholders_help')
                    ->label('Плейсхолдеры')
                    ->content('Доступны: {game}, {product}, {status}. Если шаблон пуст — берётся глобальный из «Настройки → Уведомления».'),

                Forms\Components\RichEditor::make('message_template')
                    ->label('Шаблон сообщения')
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike',
                        'bulletList', 'orderedList', 'link', 'redo', 'undo',
                    ])
                    ->maxLength(8000)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('image_path')
                    ->label('Картинка к сообщению')
                    ->image()
                    ->directory('status-posts')
                    ->disk('public')
                    ->maxSize(5120)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->columnSpanFull(),
            ])->collapsible(),

            Forms\Components\Section::make()->schema([
                Forms\Components\Toggle::make('is_notify')
                    ->label('Опубликовать в Telegram при изменении статуса')
                    ->helperText('Если статус меняется и переключатель включен — сразу запускается рассылка.')
                    ->default(true)
                    ->dehydrated(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Чит')->searchable()->sortable()->wrap(),
                Tables\Columns\TextColumn::make('cid')
                    ->label('Игра')
                    ->formatStateUsing(fn ($state) => Category::where('id', $state)->value('title') ?? '—'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn ($state) => StatusCheat::statusLabel((int) $state))
                    ->colors([
                        'success' => 1,
                        'danger' => 2,
                        'warning' => 3,
                        'secondary' => 4,
                    ]),
                Tables\Columns\IconColumn::make('image_path')
                    ->label('Img')
                    ->boolean()
                    ->trueIcon('heroicon-o-photo')
                    ->falseIcon('heroicon-o-minus'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(StatusCheat::STATUSES),
                Tables\Filters\SelectFilter::make('cid')
                    ->label('Игра')
                    ->options(fn () => Category::query()
                        ->where('cid', 0)->where('visibility', 1)
                        ->orderBy('sort')->pluck('title', 'id')->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (StatusCheat $record, array $data): StatusCheat {
                        $previousStatus = (int) $record->status;
                        $shouldNotify = (bool) ($data['is_notify'] ?? true);
                        unset($data['is_notify']);

                        $data['updated_at'] = strtotime('NOW');
                        $record->fill($data)->save();

                        if ($shouldNotify && $previousStatus !== (int) $record->status) {
                            self::dispatchBroadcast($record, $data['message_template'] ?? null);
                        }

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStatusCheats::route('/'),
        ];
    }

    /**
     * Queue a Telegram broadcast (or run sync if the queue isn't wired)
     * after a status change. Mirrors StatusCheatController::dispatchTelegramBroadcast.
     */
    public static function dispatchBroadcast(StatusCheat $status, ?string $template): void
    {
        $game = Category::where('id', $status->cid)->value('title') ?? '';
        $statusLabel = StatusCheat::statusLabel((int) $status->status);

        if ($template === null || trim($template) === '') {
            $globalTemplate = ShopSettings::getDefault()?->status_broadcast_template ?? null;
            $template = $globalTemplate && trim($globalTemplate) !== ''
                ? $globalTemplate
                : '<p>Изменение статуса чита</p><p>├ Игра: <b>{game}</b></p><p>├ Чит: <b>{product}</b></p><p>└ Статус: <b>{status}</b></p>';
        }

        /** @var TelegramPublisher $publisher */
        $publisher = app(TelegramPublisher::class);
        $rendered = $publisher->applyPlaceholders($template, [
            'game' => $game,
            'product' => $status->title,
            'status' => $statusLabel,
        ]);

        $imagePath = $status->image_path ?: (ShopSettings::getDefault()?->status_broadcast_image_path ?? null);
        $imageUrl = $imagePath ? asset('storage/' . ltrim($imagePath, '/')) : null;

        try {
            BroadcastStatusChange::dispatch($rendered, $imageUrl);
        } catch (\Throwable $e) {
            Log::warning('Filament StatusCheat: queue dispatch failed, running sync', [
                'error' => $e->getMessage(),
            ]);
            try {
                $chatId = trim((string) (ShopSettings::getDefault()?->status_broadcast_chat_id ?? ''));
                if ($chatId !== '') {
                    $publisher->sendToChat($chatId, $rendered, $imageUrl);
                }
            } catch (\Throwable $inner) {
                Log::error('Filament StatusCheat: sync broadcast failed', [
                    'error' => $inner->getMessage(),
                ]);
            }
        }
    }
}
