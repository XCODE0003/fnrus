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

                Forms\Components\Select::make('cid')
                    ->label('Категория')
                    ->options(fn () => [0 => 'Без категории'] + Category::where('cid', 0)->orderBy('sort')->pluck('title', 'id')->all())
                    ->default(0)
                    ->required(),

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('alias')->label('Alias')->searchable(),
                Tables\Columns\TextColumn::make('cid')
                    ->label('Родитель')
                    ->formatStateUsing(fn ($state) => $state == 0 ? '—' : (Category::where('id', $state)->value('title') ?? '#' . $state)),
                Tables\Columns\BadgeColumn::make('visibility')
                    ->label('Видим.')
                    ->formatStateUsing(fn ($state) => self::VISIBILITY_OPTIONS[$state] ?? $state)
                    ->colors(['success' => 1, 'info' => 2, 'warning' => 3, 'danger' => 0]),
                Tables\Columns\TextColumn::make('sort')->label('Сорт.')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visibility')->options(self::VISIBILITY_OPTIONS),
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
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
