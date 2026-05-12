<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Отзывы';
    protected static ?string $modelLabel = 'отзыв';
    protected static ?string $pluralModelLabel = 'отзывы';
    protected static ?int $navigationSort = 16;

    public static function form(Form $form): Form
    {
        // Повторяет модалку #createReview / #changeReview.
        return $form->schema([
            Forms\Components\TextInput::make('author')
                ->label('Автор')
                ->required()
                ->maxLength(255)
                ->placeholder('Имя автора'),

            Forms\Components\TextInput::make('author_link')
                ->label('Ссылка на автора')
                ->maxLength(255)
                ->placeholder('https://t.me/username'),

            Forms\Components\Textarea::make('text')
                ->label('Текст отзыва')
                ->required()
                ->rows(4)
                ->placeholder('Текст отзыва')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('avatar')
                ->label('Аватар (URL)')
                ->maxLength(255)
                ->placeholder('https://example.com/avatar.jpg')
                ->helperText('Или загрузите файл ниже — он перезапишет URL.'),

            Forms\Components\FileUpload::make('avatar_file')
                ->label('Аватар (файл)')
                ->image()
                ->directory('reviews')
                ->disk('public')
                ->visibility('public')
                ->maxSize(5120)
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/gif', 'image/webp'])
                ->dehydrated(true)
                ->helperText('jpg/png/gif/webp, до 5 MB'),

            Forms\Components\TextInput::make('link')
                ->label('Ссылка на отзыв')
                ->maxLength(255)
                ->placeholder('https://t.me/channel/123')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Аватар')
                    ->circular()
                    ->state(fn (Review $r) => self::resolveAvatarUrl($r->avatar))
                    ->size(36),
                Tables\Columns\TextColumn::make('author')->label('Автор')->searchable(),
                Tables\Columns\TextColumn::make('text')->label('Текст')->limit(80),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->formatStateUsing(fn ($state) => $state ? date('Y-m-d', (int) $state) : '—')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(static function (array $data): array {
                        $file = $data['avatar_file'] ?? null;
                        $filePath = is_array($file) ? ($file[0] ?? null) : $file;
                        if (is_string($filePath) && $filePath !== '') {
                            $data['avatar'] = '/storage/' . ltrim($filePath, '/');
                        }
                        unset($data['avatar_file']);
                        $data['author_link'] = $data['author_link'] ?? '';
                        $data['link'] = $data['link'] ?? '';
                        $data['avatar'] = $data['avatar'] ?? '';
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageReviews::route('/')];
    }

    public static function resolveAvatarUrl(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }
        return '/storage/' . ltrim($value, '/');
    }
}
