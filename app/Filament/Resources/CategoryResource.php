<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Forms\Components\AttachmentImageUpload;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Категории / Игры';
    protected static ?string $modelLabel = 'категория';
    protected static ?string $pluralModelLabel = 'категории';
    protected static ?int $navigationSort = 11;

    public const VISIBILITY_OPTIONS = [
        1 => 'Общедоступно',
        2 => 'Только на сайте',
        3 => 'Только в боте',
        0 => 'Скрыто',
    ];

    public const DISPLAY_OPTIONS = [
        0 => 'В виде слайдера',
        1 => 'По категориям',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([

                AttachmentImageUpload::make('image_site')
                    ->label('Обложка для сайта')
                    ->required(),

                AttachmentImageUpload::make('image')
                    ->label('Обложка для бота'),

                AttachmentImageUpload::make('image_hero')
                    ->label('Картинка героя (страница игры)')
                    ->helperText('Большое изображение в шапке страницы игры. Если пусто — используется дефолтное.'),

                Forms\Components\TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->minLength(2)
                    ->maxLength(100)
                    ->placeholder('Напишите название'),

                Forms\Components\TextInput::make('title_en')
                    ->label('Название (en)')
                    ->maxLength(255)
                    ->placeholder('Опционально — для англоязычной локали'),

                Forms\Components\TextInput::make('seo_description')
                    ->label('Краткое описание (для SEO)')
                    ->maxLength(255)
                    ->placeholder('Напишите краткое описание'),

                Forms\Components\TextInput::make('seo_keywords')
                    ->label('Ключевые слова (для SEO)')
                    ->maxLength(255)
                    ->placeholder('Напишите ключевые слова'),

                // ТЗ §3.3 — make it explicit whether this row is a GAME or a
                // category inside a game. Previously the parent select defaulted
                // to «Без категории», so a mis-click silently produced an
                // orphan category that no storefront page could reach.
                Forms\Components\Radio::make('entry_kind')
                    ->label('Что создаём')
                    ->options([
                        'game' => 'Игру (верхний уровень)',
                        'category' => 'Категорию внутри игры',
                    ])
                    ->default(fn (?Category $record) => $record && (int) $record->cid !== 0 ? 'category' : 'game')
                    ->dehydrated(false)
                    ->live()
                    ->inline()
                    ->inlineLabel(false)
                    ->columnSpanFull(),

                Forms\Components\Select::make('cid')
                    ->label('Игра')
                    ->helperText('Категория (Android, iOS, ПК …) всегда принадлежит игре.')
                    ->options(fn () => Category::where('cid', 0)->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->visible(fn (Forms\Get $get) => $get('entry_kind') === 'category')
                    ->required(fn (Forms\Get $get) => $get('entry_kind') === 'category')
                    // a game keeps cid = 0; a category must name its game
                    ->dehydrateStateUsing(fn ($state, Forms\Get $get) => $get('entry_kind') === 'category' ? (int) $state : 0)
                    ->rules([
                        fn (Forms\Get $get) => function (string $attr, $value, \Closure $fail) use ($get) {
                            if ($get('entry_kind') === 'category' && (int) $value === 0) {
                                $fail('Выберите игру, которой принадлежит категория.');
                            }
                        },
                    ]),

                // ТЗ §7 — platform of the game, drives the catalog filter
                // ("Все игры / ПК игры / Мобильные игры"). Only meaningful for a
                // top-level game (cid = 0); the child platform buckets inherit it.
                Forms\Components\Select::make('platform')
                    ->label('Платформа игры')
                    ->helperText('По этому полю работает фильтр в каталоге. Обязательно для игры.')
                    ->options([
                        'pc' => 'ПК',
                        'mobile' => 'Мобильные',
                        'pc mobile' => 'ПК и мобильные',
                    ])
                    ->default('pc mobile')
                    ->native(false)
                    ->visible(fn (Forms\Get $get) => $get('entry_kind') === 'game')
                    ->required(fn (Forms\Get $get) => $get('entry_kind') === 'game'),

                Forms\Components\RichEditor::make('description')
                    ->label('Описание')
                    ->toolbarButtons([
                        'attachFiles', 'blockquote', 'bold', 'bulletList',
                        'codeBlock', 'h2', 'h3', 'italic', 'link',
                        'orderedList', 'strike', 'underline', 'redo', 'undo',
                    ])
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('covers')
                    ->fileAttachmentsVisibility('public')
                    ->maxLength(8000)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('count_column')
                    ->label('Кол-во колонок')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->default(1)
                    ->required()
                    ->placeholder('Введите число от 1 до 5'),

                Forms\Components\TextInput::make('alias')
                    ->label('Короткий адрес')
                    ->required()
                    ->minLength(2)
                    ->maxLength(100)
                    ->placeholder('Напишите адрес'),

                Forms\Components\Select::make('visibility')
                    ->label('Видимость')
                    ->options(self::VISIBILITY_OPTIONS)
                    ->default(1)
                    ->required(),

                Forms\Components\Select::make('display_products')
                    ->label('Отображение товаров')
                    ->options(self::DISPLAY_OPTIONS)
                    ->default(0)
                    ->required(),

                Forms\Components\Toggle::make('disable_web_page_preview')
                    ->label('Отключить предпросмотр ссылок'),

                Forms\Components\Toggle::make('image_spoiler')
                    ->label('Включить спойлер на изображение'),
            ])->columns(2),
        ]);
    }

    /** id => title of every top-level game, memoised per request. */
    protected static ?array $gameTitles = null;

    protected static function gameTitles(): array
    {
        return self::$gameTitles ??= Category::where('cid', 0)
            ->pluck('title', 'id')
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('alias')->label('Alias')->searchable(),
                // ТЗ §3.1 — a category must read as "which game it belongs to".
                // Titles are resolved once per render, not per row (was N+1).
                Tables\Columns\TextColumn::make('cid')
                    ->label('Игра')
                    ->badge()
                    ->color(fn ($state) => (int) $state === 0 ? 'gray' : 'info')
                    ->formatStateUsing(fn ($state) => (int) $state === 0
                        ? 'Игра'
                        : (self::gameTitles()[(int) $state] ?? '#' . $state))
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('visibility')
                    ->label('Видим.')
                    ->formatStateUsing(fn ($state) => self::VISIBILITY_OPTIONS[$state] ?? $state)
                    ->colors(['success' => 1, 'info' => 2, 'warning' => 3, 'danger' => 0]),
                Tables\Columns\TextColumn::make('platform')
                    ->label('Платформа')
                    ->formatStateUsing(fn ($state) => [
                        'pc' => 'ПК',
                        'mobile' => 'Мобильные',
                        'pc mobile' => 'ПК и моб.',
                    ][$state] ?? '—'),
                Tables\Columns\TextColumn::make('sort')->label('Сорт.')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visibility')->options(self::VISIBILITY_OPTIONS),
                Tables\Filters\SelectFilter::make('platform')->label('Платформа')->options([
                    'pc' => 'ПК',
                    'mobile' => 'Мобильные',
                    'pc mobile' => 'ПК и мобильные',
                ]),
                Tables\Filters\Filter::make('top_level')->label('Только корневые')
                    ->query(fn ($query) => $query->where('cid', 0)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(static function (array $data): array {
                        $data['image_spoiler'] = (int) ($data['image_spoiler'] ?? 0);
                        $data['disable_web_page_preview'] = (int) ($data['disable_web_page_preview'] ?? 0);
                        $data['updated_at'] = time();
                        return $data;
                    }),
                // ТЗ §3.3 — deleting a game used to orphan its categories.
                Tables\Actions\DeleteAction::make()
                    ->before(function (Category $record, Tables\Actions\DeleteAction $action): void {
                        if ((int) $record->cid !== 0) {
                            return;
                        }
                        $children = Category::where('cid', $record->id)->count();
                        if ($children > 0) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Сначала удалите категории игры')
                                ->body("У игры «{$record->title}» ещё {$children} категор." . ' Удаление оставило бы их без игры.')
                                ->send();
                            $action->cancel();
                        }
                    }),
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
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
