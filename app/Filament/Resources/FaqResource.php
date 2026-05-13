<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use App\Models\Instruction;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'FAQ';
    protected static ?string $modelLabel = 'вопрос';
    protected static ?string $pluralModelLabel = 'вопросы';
    protected static ?int $navigationSort = 14;

    public const VISIBILITY_OPTIONS = [
        1 => 'Общедоступно',
        0 => 'Скрыто из общего доступа',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('in_id')
                ->label('Расположение')
                ->options(fn () => [0 => 'По умолчанию'] + Instruction::orderBy('id')->pluck('title', 'id')->all())
                ->default(0)
                ->required(),

            Forms\Components\TextInput::make('question')
                ->label('Вопрос')
                ->required()
                ->minLength(4)
                ->maxLength(255)
                ->placeholder('Укажите вопрос'),

            Forms\Components\TextInput::make('question_en')
                ->label('Вопрос (en)')
                ->maxLength(255)
                ->placeholder('Опционально — для англоязычной локали'),

            TiptapEditor::make('answer')
                ->label('Ответ')
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
                ->columnSpanFull(),

            TiptapEditor::make('answer_en')
                ->label('Ответ (en)')
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
                ->columnSpanFull(),

            Forms\Components\Select::make('visibility')
                ->label('Видимость')
                ->options(self::VISIBILITY_OPTIONS)
                ->default(1)
                ->required(),
            // Порядок не редактируется вручную — только перетаскиванием в таблице
            // (как в старой админке: POST /faq/sort).
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('question')->label('Вопрос')->searchable()->limit(80),
                Tables\Columns\TextColumn::make('in_id')
                    ->label('Расположение')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'По умолчанию' : (Instruction::where('id', $state)->value('title') ?? '#' . $state)),
                Tables\Columns\BadgeColumn::make('visibility')
                    ->label('Видим.')
                    ->formatStateUsing(fn ($state) => self::VISIBILITY_OPTIONS[(int) $state] ?? $state)
                    ->colors(['success' => 1, 'danger' => 0]),
                Tables\Columns\TextColumn::make('sort')->label('Сорт.')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('visibility')->options(self::VISIBILITY_OPTIONS),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(static function (array $data): array {
                        $data['updated_at'] = time();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageFaqs::route('/')];
    }
}
