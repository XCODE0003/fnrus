<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EmailBroadcastResource\Pages;
use App\Models\EmailBroadcast;
use App\Models\User;
use App\Services\EmailBroadcastService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class EmailBroadcastResource extends Resource
{
    protected static ?string $model = EmailBroadcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Маркетинг';
    protected static ?string $navigationLabel = 'Email-рассылки';
    protected static ?string $modelLabel = 'рассылка';
    protected static ?string $pluralModelLabel = 'рассылки';
    protected static ?int $navigationSort = 50;

    private const STATUS_LABELS = [
        EmailBroadcast::STATUS_DRAFT => 'Черновик',
        EmailBroadcast::STATUS_QUEUED => 'В очереди',
        EmailBroadcast::STATUS_SENDING => 'Отправляется',
        EmailBroadcast::STATUS_SENT => 'Отправлено',
        EmailBroadcast::STATUS_FAILED => 'Ошибка',
        EmailBroadcast::STATUS_CANCELLED => 'Отменено',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('subject')
                    ->label('Тема')
                    ->placeholder('Тема письма')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Placeholder::make('placeholders_help')
                    ->label('Плейсхолдеры')
                    ->content('В теле доступен плейсхолдер {email} — он будет заменён на email конкретного получателя.'),

                Forms\Components\RichEditor::make('body_html')
                    ->label('Тело письма')
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike',
                        'bulletList', 'orderedList', 'link', 'redo', 'undo', 'h2', 'h3',
                    ])
                    ->maxLength(50000)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('body_text')
                    ->label('Plain-text версия (опционально)')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make()->schema([
                Forms\Components\Placeholder::make('warning')
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<div class="rounded bg-warning-50 dark:bg-warning-500/10 text-warning-700 dark:text-warning-300 p-3 text-sm">'
                        . 'Рассылка отправляется только пользователям с подтверждённым email и включённой опцией маркетинга '
                        . '(<code>email_notify_marketing=1</code>). В письмо автоматически добавляется ссылка на отписку.'
                        . '</div>'
                    )),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Тема')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::STATUS_LABELS[$state] ?? '—')
                    ->color(fn ($state) => match ($state) {
                        EmailBroadcast::STATUS_DRAFT => 'gray',
                        EmailBroadcast::STATUS_QUEUED, EmailBroadcast::STATUS_SENDING => 'warning',
                        EmailBroadcast::STATUS_SENT => 'success',
                        EmailBroadcast::STATUS_FAILED, EmailBroadcast::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('recipients_total')
                    ->label('Аудитория')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('recipients_sent')
                    ->label('Отправлено')
                    ->numeric()
                    ->color('success'),

                Tables\Columns\TextColumn::make('recipients_failed')
                    ->label('Ошибок')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('admin_id')
                    ->label('Админ')
                    ->formatStateUsing(fn ($state) => $state ? (User::query()->where('id', $state)->value('username') ?: '#' . $state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? date('d.m.Y H:i', (int) $state) : '—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(self::STATUS_LABELS),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (EmailBroadcast $record) => in_array(
                        $record->status,
                        [EmailBroadcast::STATUS_DRAFT, EmailBroadcast::STATUS_FAILED],
                        true
                    )),

                Tables\Actions\Action::make('preview')
                    ->label('Превью')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Превью письма')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть')
                    ->modalContent(function (EmailBroadcast $record): HtmlString {
                        try {
                            /** @var EmailBroadcastService $svc */
                            $svc = app(EmailBroadcastService::class);
                            $sanitized = $svc->sanitize($record->body_html ?? '');
                            $rendered = $svc->personalise($sanitized, ['email' => 'preview@example.com']);
                            return new HtmlString(
                                '<iframe srcdoc="' . htmlspecialchars($rendered, ENT_QUOTES) . '" '
                                . 'style="width:100%;height:520px;border:1px solid #2a2a2a;border-radius:6px;background:#fff"></iframe>'
                            );
                        } catch (\Throwable $e) {
                            return new HtmlString('<div class="text-danger-600">' . e($e->getMessage()) . '</div>');
                        }
                    }),

                Tables\Actions\Action::make('send')
                    ->label('Отправить')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (EmailBroadcast $record) => in_array(
                        $record->status,
                        [EmailBroadcast::STATUS_DRAFT, EmailBroadcast::STATUS_FAILED],
                        true
                    ))
                    ->modalHeading('Подтверждение отправки')
                    ->modalDescription(fn (EmailBroadcast $record) => sprintf(
                        'Эта операция запустит рассылку «%s» на %s подписчиков.',
                        $record->subject,
                        number_format(self::audienceEligible(), 0, '.', ' ')
                    ))
                    ->form([
                        Forms\Components\TextInput::make('confirm')
                            ->label('Введите ОТПРАВИТЬ для подтверждения')
                            ->required()
                            ->rule('in:ОТПРАВИТЬ'),
                    ])
                    ->action(function (EmailBroadcast $record): void {
                        try {
                            /** @var EmailBroadcastService $svc */
                            $svc = app(EmailBroadcastService::class);
                            $result = $svc->start($record);
                            Notification::make()
                                ->success()
                                ->title('Поставлено в очередь')
                                ->body('Очередь: ' . $result['queued'] . ', пропущено: ' . $result['skipped'])
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()->danger()->title('Ошибка отправки')->body($e->getMessage())->send();
                        }
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (EmailBroadcast $record) => in_array(
                        $record->status,
                        [EmailBroadcast::STATUS_DRAFT, EmailBroadcast::STATUS_SENT, EmailBroadcast::STATUS_FAILED, EmailBroadcast::STATUS_CANCELLED],
                        true
                    )),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEmailBroadcasts::route('/'),
        ];
    }

    /** Аудитория, как в /api/email-broadcasts/audience: подписаны на маркетинг + email не пуст. */
    public static function audienceEligible(): int
    {
        return (int) DB::table('users')
            ->where('email_notify_marketing', 1)
            ->whereRaw("email <> ''")
            ->count();
    }

    public static function audienceTotal(): int
    {
        return (int) DB::table('users')
            ->whereRaw("email <> ''")
            ->count();
    }

    public static function audienceOptedOut(): int
    {
        return self::audienceTotal() - self::audienceEligible();
    }
}
