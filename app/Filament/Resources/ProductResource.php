<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Forms\Components\AttachmentImageUpload;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use App\Models\StatusCheat;
use App\Models\Tariff;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Товары / Софт';
    protected static ?string $modelLabel = 'товар';
    protected static ?string $pluralModelLabel = 'товары';
    protected static ?int $navigationSort = 10;

    public const VISIBILITY_OPTIONS = [
        1 => 'Общедоступно',
        2 => 'Только на сайте',
        3 => 'Только в боте',
        0 => 'Скрыто',
    ];

    public const HACK_STATUS_OPTIONS = [
        0 => 'Не требуется',
        1 => 'Jailbreak',
        2 => 'Без Jailbreak',
        3 => 'Root',
        4 => 'Без Root',
        5 => 'С обходом',
        6 => 'Без обхода',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make()->columns(2)->schema([

                // ─── Левая колонка ───────────────────────────────────────────
                Forms\Components\Group::make()->schema([

                    AttachmentImageUpload::make('image_site')
                        ->label('Обложка для сайта')
                        ->required(),

                    AttachmentImageUpload::make('image')
                        ->label('Обложка для бота'),

                    AttachmentImageUpload::makeMultiple('gallery')
                        ->label('Галерея изображений'),

                    Forms\Components\TextInput::make('title')
                        ->label('Название')
                        ->required()
                        ->minLength(2)
                        ->maxLength(100)
                        ->placeholder('Напишите название'),

                    Forms\Components\TextInput::make('seo_description')
                        ->label('Краткое описание (для SEO)')
                        ->maxLength(255)
                        ->placeholder('Напишите краткое описание'),

                    Forms\Components\TextInput::make('seo_keywords')
                        ->label('Ключевые слова (для SEO)')
                        ->maxLength(255)
                        ->placeholder('Напишите ключевые слова'),

                    Forms\Components\Select::make('cid')
                        ->label('Категория')
                        ->options(fn () => Category::orderBy('sort')->pluck('title', 'id')->all())
                        ->searchable()
                        ->required(),

                    TiptapEditor::make('description')
                        ->label('Описание')
                        ->profile('default')
                        ->tools([
                            'heading', 'bullet-list', 'ordered-list', 'blockquote', '|',
                            'bold', 'italic', 'underline', 'strike', 'color', 'highlight', '|',
                            'align-left', 'align-center', 'align-right', '|',
                            'link', 'media', '|',
                            'undo', 'redo',
                        ])
                        ->disk('public')
                        ->directory('covers')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/gif'])
                        ->maxFileSize(5120)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('advantages')
                        ->label('Преимущества')
                        ->rows(4)
                        ->required()
                        ->placeholder('Каждое преимущество с новой строки')
                        ->afterStateHydrated(function (Forms\Components\Textarea $component, $state): void {
                            if (is_string($state) && $state !== '') {
                                $decoded = json_decode($state, true);
                                if (is_array($decoded)) {
                                    $component->state(implode("\n", $decoded));
                                }
                            }
                        })
                        ->dehydrateStateUsing(function ($state): string {
                            $lines = array_values(array_filter(
                                array_map('trim', explode("\n", (string) $state)),
                                static fn ($line) => $line !== ''
                            ));
                            return json_encode($lines, JSON_UNESCAPED_UNICODE);
                        }),
                ]),

                // ─── Правая колонка ──────────────────────────────────────────
                Forms\Components\Group::make()->schema([

                    Forms\Components\Repeater::make('functional_data')
                        ->label('Функционал')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Заголовок блока')
                                ->required(),
                            Forms\Components\Textarea::make('lines')
                                ->label('Пункты (каждый с новой строки)')
                                ->rows(4),
                        ])
                        ->addActionLabel('Добавить блок')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Repeater $component, ?Product $record): void {
                            if (! $record || ! $record->functional) {
                                return;
                            }
                            $decoded = json_decode((string) $record->functional, true) ?: [];
                            $state = [];
                            foreach ($decoded as $block) {
                                $state[] = [
                                    'title' => $block['title'] ?? '',
                                    'lines' => isset($block['lines']) && is_array($block['lines'])
                                        ? implode("\n", $block['lines'])
                                        : '',
                                ];
                            }
                            $component->state($state);
                        }),

                    Forms\Components\Repeater::make('tariffs_data')
                        ->label('Тарифы')
                        ->schema([
                            Forms\Components\TextInput::make('days')
                                ->label('Кол-во дней')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('price')
                                ->label('Цена')
                                ->numeric()
                                ->required()
                                ->rule('regex:/^\d+(\.\d{1,2})?$/'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Добавить тариф')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Repeater $component, ?Product $record): void {
                            if (! $record) {
                                return;
                            }
                            $tariffs = Tariff::where('pid', $record->id)->orderBy('sort')->get();
                            $state = [];
                            foreach ($tariffs as $t) {
                                $state[] = ['days' => $t->title, 'price' => $t->price];
                            }
                            $component->state($state);
                        }),

                    Forms\Components\TextInput::make('system_versions')
                        ->label('Поддерживаемые языки')
                        ->required()
                        ->minLength(4)
                        ->maxLength(100)
                        ->placeholder('Напишите языки через запятую'),

                    Forms\Components\TextInput::make('system_auth')
                        ->label('Авторизация в софте')
                        ->required()
                        ->minLength(4)
                        ->maxLength(100)
                        ->placeholder('Через что происходит авторизация'),

                    Forms\Components\Select::make('status_id')
                        ->label('Статус софта')
                        ->options(fn () => StatusCheat::query()->pluck('title', 'id')->all())
                        ->searchable()
                        ->required(),

                    Forms\Components\TextInput::make('link_video')
                        ->label('Ссылка на видеообзор')
                        ->maxLength(100)
                        ->placeholder('Вставьте ссылку'),

                    Forms\Components\TextInput::make('alias')
                        ->label('Короткий адрес')
                        ->required()
                        ->minLength(2)
                        ->maxLength(100)
                        ->placeholder('Напишите короткий адрес'),

                    Forms\Components\Select::make('visibility')
                        ->label('Видимость')
                        ->options(self::VISIBILITY_OPTIONS)
                        ->default(1)
                        ->required(),

                    Forms\Components\Select::make('hack_status')
                        ->label('Взлом')
                        ->options(self::HACK_STATUS_OPTIONS)
                        ->default(0)
                        ->required(),

                    Forms\Components\Toggle::make('disable_web_page_preview')
                        ->label('Отключить предпросмотр ссылок'),

                    Forms\Components\Toggle::make('image_spoiler')
                        ->label('Включить спойлер на изображение'),

                    Forms\Components\Toggle::make('count_max')
                        ->label('Разрешить покупку только один раз')
                        ->afterStateHydrated(function (Forms\Components\Toggle $component, $state): void {
                            $component->state((int) $state === 1);
                        })
                        ->dehydrateStateUsing(fn ($state) => $state ? 1 : 0),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable()->sortable()->wrap(),
                Tables\Columns\TextColumn::make('cid')
                    ->label('Категория')
                    ->formatStateUsing(fn ($state) => Category::where('id', $state)->value('title') ?? '#' . $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('count_sales')->label('Продаж')->sortable(),
                Tables\Columns\TextColumn::make('materials_available')
                    ->label('Материалов')
                    ->state(fn (Product $record): int => \App\Models\Material::where('pid', $record->id)->where('status', 1)->count())
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (int) $state <= 0   => 'danger',
                        (int) $state < 5    => 'warning',
                        default             => 'success',
                    })
                    ->tooltip('Доступно к продаже (status=1)'),
                Tables\Columns\BadgeColumn::make('visibility')
                    ->label('Видим.')
                    ->formatStateUsing(fn ($state) => self::VISIBILITY_OPTIONS[$state] ?? $state)
                    ->colors(['success' => 1, 'info' => 2, 'warning' => 3, 'danger' => 0]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visibility')->options(self::VISIBILITY_OPTIONS),
                Tables\Filters\SelectFilter::make('cid')
                    ->label('Категория')
                    ->options(fn () => Category::orderBy('sort')->pluck('title', 'id')->all())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
