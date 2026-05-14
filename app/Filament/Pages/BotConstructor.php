<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Forms\Components\AttachmentImageUpload;
use App\Models\Button;
use App\Models\ButtonSettings;
use App\Models\ChannelSub;
use App\Models\ChannelSubSettings;
use App\Models\Shop;
use App\Models\Text;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * Конструктор бота — single-page editor for everything the legacy
 * /admin/settings/constructor page covered:
 *
 *   – texts.{welcome,agreement,after_payment}: image, text, toggles
 *   – channels_sub_settings + channels_sub (mandatory subscription)
 *   – buttons_settings.count_columns + buttons (menu buttons grid)
 *
 * Built as a single Filament Page rather than a Resource so the whole
 * layout matches the old two-column screen the user is used to.
 */
class BotConstructor extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Конструктор бота';
    protected static ?string $title = 'Конструктор бота';
    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.bot-constructor';
    protected static ?string $slug = 'settings/bot-constructor';

    public ?array $data = [];

    public function mount(): void
    {
        $shop = Shop::getDefault();
        if (! $shop) {
            $this->form->fill([]);
            return;
        }

        $sid = (int) $shop->id;

        $welcome = Text::getByType($sid, 'welcome');
        $agreement = Text::getByType($sid, 'agreement');
        $afterPayment = Text::getByType($sid, 'after_payment');
        $channelSettings = ChannelSubSettings::getByShopID($sid);
        $channels = ChannelSub::getAll($sid);
        $buttonSettings = ButtonSettings::getByShopID($sid);
        $buttons = Button::where('sid', $sid)->orderBy('sort')->get();

        $this->form->fill([
            'welcome_image'           => $welcome->image ?? null,
            'welcome_text'            => $welcome->text ?? '',
            'welcome_disable_preview' => (bool) ($welcome->disable_web_page_preview ?? false),
            'welcome_spoiler'         => (bool) ($welcome->is_spoiler ?? false),

            'channels_active'        => (bool) ($channelSettings->is_active ?? false),
            'channels_text'          => $channelSettings->text ?? '',
            'channels_button_check'  => $channelSettings->button_check ?? 'Я подписался',
            'channels_columns'       => (int) ($channelSettings->count_columns ?? 1),
            'channels'               => $channels
                ->map(fn ($c) => [
                    'cid'       => (string) $c->cid,
                    'title'     => (string) $c->title,
                    'link'      => (string) $c->link,
                    'is_active' => (bool) $c->is_active,
                ])
                ->all(),

            'buttons_columns' => (int) ($buttonSettings->count_columns ?? 1),
            'buttons'         => $buttons
                ->map(fn (Button $b) => [
                    'id'           => (int) $b->id,
                    'title'        => (string) $b->title,
                    'text'         => (string) $b->text,
                    'image'        => $b->image ?: null,
                    'visible'      => (bool) $b->visible,
                    'link_buttons' => self::decodeLinkButtons($b->buttons),
                ])
                ->all(),

            'agreement_active' => (bool) ($agreement->is_active ?? false),
            'agreement_text'   => $agreement->text ?? '',

            'after_payment_active' => (bool) ($afterPayment->is_active ?? false),
            'after_payment_text'   => $afterPayment->text ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        $columnOptions = [1 => '1 колонка', 2 => '2 колонки', 3 => '3 колонки'];

        $welcomeBlock = Forms\Components\Section::make('Приветственное сообщение')
            ->icon('heroicon-o-document-text')
            ->schema([
                AttachmentImageUpload::make('welcome_image')
                    ->label('Изображение'),
                Forms\Components\RichEditor::make('welcome_text')
                    ->label('Текст')
                    ->placeholder('👋 Привет, {first_name}! Доступная подстановка — {first_name}.')
                    ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList', 'redo', 'undo'])
                    ->maxLength(4096)
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('welcome_hint')
                    ->label('')
                    ->content('Подстановка: {first_name} — имя пользователя.'),
                Forms\Components\Toggle::make('welcome_disable_preview')
                    ->label('Отключить предпросмотр ссылок')
                    ->inline(false),
                Forms\Components\Toggle::make('welcome_spoiler')
                    ->label('Включить спойлер на изображение')
                    ->inline(false),
            ]);

        $channelsBlock = Forms\Components\Section::make('Обязательная подписка на канал')
            ->icon('heroicon-o-user-plus')
            ->schema([
                Forms\Components\Toggle::make('channels_active')
                    ->label('Включено')
                    ->inline(false),
                Forms\Components\RichEditor::make('channels_text')
                    ->label('Текст')
                    ->toolbarButtons(['bold', 'italic', 'underline', 'link'])
                    ->maxLength(4096)
                    ->columnSpanFull(),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('channels_button_check')
                        ->label('Текст кнопки «Проверка»')
                        ->maxLength(64),
                    Forms\Components\Select::make('channels_columns')
                        ->label('Кнопок в строке')
                        ->options($columnOptions)
                        ->required(),
                ]),
                Forms\Components\Repeater::make('channels')
                    ->label('Каналы')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('cid')
                                ->label('Channel ID')
                                ->required()
                                ->maxLength(64),
                            Forms\Components\TextInput::make('title')
                                ->label('Название')
                                ->required()
                                ->maxLength(120),
                            Forms\Components\TextInput::make('link')
                                ->label('Ссылка')
                                ->required()
                                ->url()
                                ->maxLength(255),
                        ]),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->addActionLabel('Добавить канал')
                    ->columnSpanFull(),
            ]);

        $buttonsBlock = Forms\Components\Section::make('Кнопки меню')
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                Forms\Components\Select::make('buttons_columns')
                    ->label('Кнопок в строке')
                    ->options($columnOptions)
                    ->required(),
                Forms\Components\Repeater::make('buttons')
                    ->label('Кнопки')
                    ->schema([
                        Forms\Components\Hidden::make('id'),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Название кнопки')
                                ->required()
                                ->maxLength(64),
                            Forms\Components\Toggle::make('visible')
                                ->label('Видимая')
                                ->default(true)
                                ->inline(false),
                        ]),
                        AttachmentImageUpload::make('image')
                            ->label('Изображение'),
                        Forms\Components\RichEditor::make('text')
                            ->label('Текст при нажатии')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList'])
                            ->maxLength(4096)
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('link_buttons')
                            ->label('Кнопки-ссылки под сообщением')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('text')
                                        ->label('Текст кнопки')
                                        ->required()
                                        ->maxLength(64),
                                    Forms\Components\TextInput::make('url')
                                        ->label('Ссылка')
                                        ->required()
                                        ->url()
                                        ->maxLength(255),
                                ]),
                            ])
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['text'] ?? null)
                            ->addActionLabel('Добавить кнопку-ссылку')
                            ->columnSpanFull(),
                    ])
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->addActionLabel('Добавить кнопку')
                    ->columnSpanFull(),
            ]);

        $agreementBlock = Forms\Components\Section::make('Принудительное соглашение')
            ->icon('heroicon-o-shield-check')
            ->schema([
                Forms\Components\Toggle::make('agreement_active')
                    ->label('Включено')
                    ->inline(false),
                Forms\Components\RichEditor::make('agreement_text')
                    ->label('Текст')
                    ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList'])
                    ->maxLength(4096)
                    ->columnSpanFull(),
            ]);

        $afterPaymentBlock = Forms\Components\Section::make('Текст после покупки')
            ->icon('heroicon-o-shopping-bag')
            ->schema([
                Forms\Components\Toggle::make('after_payment_active')
                    ->label('Включено')
                    ->inline(false),
                Forms\Components\RichEditor::make('after_payment_text')
                    ->label('Текст')
                    ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList'])
                    ->maxLength(4096)
                    ->columnSpanFull(),
            ]);

        return $form
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Forms\Components\Group::make([
                        $welcomeBlock,
                        $channelsBlock,
                    ]),
                    Forms\Components\Group::make([
                        $buttonsBlock,
                        $agreementBlock,
                        $afterPaymentBlock,
                    ]),
                ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $shop = Shop::getDefault();
        if (! $shop) {
            Notification::make()->danger()->title('Магазин не настроен')->send();
            return;
        }

        $sid = (int) $shop->id;
        $data = $this->form->getState();
        $now = time();

        DB::transaction(function () use ($sid, $data, $now): void {
            // ── texts: welcome / agreement / after_payment ──────────────
            $this->upsertText($sid, 'welcome', [
                'text'                     => (string) ($data['welcome_text'] ?? ''),
                'image'                    => (string) ($data['welcome_image'] ?? ''),
                'disable_web_page_preview' => (int) (bool) ($data['welcome_disable_preview'] ?? false),
                'is_spoiler'               => (int) (bool) ($data['welcome_spoiler'] ?? false),
                'is_active'                => 1,
                'updated_at'               => $now,
            ]);
            $this->upsertText($sid, 'agreement', [
                'text'                     => (string) ($data['agreement_text'] ?? ''),
                'image'                    => '',
                'disable_web_page_preview' => 0,
                'is_spoiler'               => 0,
                'is_active'                => (int) (bool) ($data['agreement_active'] ?? false),
                'updated_at'               => $now,
            ]);
            $this->upsertText($sid, 'after_payment', [
                'text'                     => (string) ($data['after_payment_text'] ?? ''),
                'image'                    => '',
                'disable_web_page_preview' => 0,
                'is_spoiler'               => 0,
                'is_active'                => (int) (bool) ($data['after_payment_active'] ?? false),
                'updated_at'               => $now,
            ]);

            // ── channels_sub_settings ──────────────────────────────────
            DB::table('channels_sub_settings')->updateOrInsert(
                ['sid' => $sid],
                [
                    'text'          => (string) ($data['channels_text'] ?? ''),
                    'button_check'  => (string) ($data['channels_button_check'] ?? 'Я подписался'),
                    'count_columns' => (int) ($data['channels_columns'] ?? 1),
                    'is_active'     => (int) (bool) ($data['channels_active'] ?? false),
                ],
            );

            // ── channels_sub (insert/update/delete by cid+sid pair) ────
            $submitted = collect($data['channels'] ?? [])->values();
            $sort = 0;
            $keepCids = [];
            foreach ($submitted as $row) {
                $cid = (string) ($row['cid'] ?? '');
                if ($cid === '') {
                    continue;
                }
                $keepCids[] = $cid;
                DB::table('channels_sub')->updateOrInsert(
                    ['sid' => $sid, 'cid' => $cid],
                    [
                        'title'      => (string) ($row['title'] ?? ''),
                        'link'       => (string) ($row['link'] ?? ''),
                        'is_active'  => (int) (bool) ($row['is_active'] ?? true),
                        'sort'       => $sort++,
                        'created_at' => $now,
                    ],
                );
            }
            DB::table('channels_sub')
                ->where('sid', $sid)
                ->when($keepCids, fn ($q) => $q->whereNotIn('cid', $keepCids))
                ->delete();

            // ── buttons_settings ───────────────────────────────────────
            DB::table('buttons_settings')->updateOrInsert(
                ['sid' => $sid],
                [
                    'count_columns' => (int) ($data['buttons_columns'] ?? 1),
                    'updated_at'    => $now,
                ],
            );

            // ── buttons (preserve ids when possible) ───────────────────
            $submittedButtons = collect($data['buttons'] ?? [])->values();
            $sort = 0;
            $keepIds = [];
            foreach ($submittedButtons as $row) {
                $id = (int) ($row['id'] ?? 0);
                $payload = [
                    'sid'                      => $sid,
                    'title'                    => (string) ($row['title'] ?? ''),
                    'text'                     => (string) ($row['text'] ?? ''),
                    'image'                    => (string) ($row['image'] ?? ''),
                    'disable_web_page_preview' => 0,
                    'image_spoiler'            => 0,
                    'buttons'                  => self::encodeLinkButtons($row['link_buttons'] ?? []),
                    'visible'                  => (int) (bool) ($row['visible'] ?? true),
                    'sort'                     => $sort++,
                    'updated_at'               => $now,
                ];

                if ($id > 0 && Button::where('id', $id)->where('sid', $sid)->exists()) {
                    Button::where('id', $id)->update($payload);
                    $keepIds[] = $id;
                    continue;
                }

                $payload['created_at'] = $now;
                $payload['type']       = 0;
                $newId = DB::table('buttons')->insertGetId($payload);
                $keepIds[] = (int) $newId;
            }
            Button::where('sid', $sid)
                ->when($keepIds, fn ($q) => $q->whereNotIn('id', $keepIds))
                ->delete();
        });

        Notification::make()->success()->title('Сохранено')->send();
    }

    private function upsertText(int $sid, string $type, array $payload): void
    {
        $payload['type'] = $type;
        $existing = Text::where('sid', $sid)->where('type', $type)->first();
        if ($existing) {
            Text::where('id', $existing->id)->update($payload);
            return;
        }
        $payload['sid']        = $sid;
        $payload['created_at'] = $payload['updated_at'];
        DB::table('texts')->insert($payload);
    }

    /**
     * Парсит JSON из `buttons.buttons` в массив для Repeater'а.
     * Принимает только пары text+url (callback_data игнорируем — они
     * привязаны к старому бот-роутингу и редактируются отдельно).
     */
    private static function decodeLinkButtons(?string $raw): array
    {
        if ($raw === null || $raw === '' || $raw === '[]' || $raw === '{}') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            $text = (string) ($item['text'] ?? '');
            $url  = (string) ($item['url'] ?? '');
            if ($text === '' || $url === '') {
                continue;
            }
            $out[] = ['text' => $text, 'url' => $url];
        }
        return $out;
    }

    /**
     * Сериализует массив [{text,url},…] в JSON для колонки `buttons.buttons`.
     * Возвращает "[]" для пустого набора — колонка NOT NULL.
     */
    private static function encodeLinkButtons(mixed $rows): string
    {
        if (! is_array($rows) || $rows === []) {
            return '[]';
        }
        $clean = [];
        foreach ($rows as $row) {
            if (! is_array($row)) { continue; }
            $text = trim((string) ($row['text'] ?? ''));
            $url  = trim((string) ($row['url']  ?? ''));
            if ($text === '' || $url === '') { continue; }
            $clean[] = ['text' => $text, 'url' => $url];
        }
        return json_encode(array_values($clean), JSON_UNESCAPED_UNICODE);
    }
}
