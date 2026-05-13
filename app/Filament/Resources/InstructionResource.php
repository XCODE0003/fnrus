<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\InstructionResource\Pages;
use App\Models\Instruction;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class InstructionResource extends Resource
{
    protected static ?string $model = Instruction::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Инструкции';
    protected static ?string $modelLabel = 'инструкция';
    protected static ?string $pluralModelLabel = 'инструкции';
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        // Повторяет модалку #createInstruction.
        return $form->schema([
            Forms\Components\Select::make('pids')
                ->label('Товары')
                ->multiple()
                ->options(fn () => Product::orderBy('id', 'desc')->limit(1000)->pluck('title', 'id')->all())
                ->searchable()
                ->required()
                ->afterStateHydrated(function (Forms\Components\Select $component, $state): void {
                    if (is_string($state) && $state !== '') {
                        $decoded = json_decode($state, true);
                        if (is_array($decoded)) {
                            // pids в БД хранится как массив строк — приводим к int для UI.
                            $component->state(array_map('intval', array_values($decoded)));
                        }
                    }
                })
                ->dehydrateStateUsing(static function ($state): string {
                    $values = array_values((array) $state);
                    // В БД pids исторически хранится как JSON массив строк (["1","2"]).
                    $values = array_map(static fn ($v) => (string) $v, $values);
                    return json_encode($values, JSON_UNESCAPED_UNICODE);
                })
                ->columnSpanFull(),

            Forms\Components\TextInput::make('title')
                ->label('Название')
                ->required()
                ->minLength(4)
                ->maxLength(255)
                ->placeholder('Введите название'),

            Forms\Components\TextInput::make('alias')
                ->label('Короткий адрес')
                ->required()
                ->minLength(2)
                ->maxLength(100)
                ->placeholder('Введите короткий адрес'),

            Forms\Components\RichEditor::make('body')
                ->label('Текст инструкции')
                ->required()
                ->toolbarButtons([
                    'attachFiles', 'blockquote', 'bold', 'bulletList',
                    'codeBlock', 'h2', 'h3', 'italic', 'link',
                    'orderedList', 'strike', 'underline', 'redo', 'undo',
                ])
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsDirectory('covers')
                ->fileAttachmentsVisibility('public')
                ->saveUploadedFileAttachmentsUsing(fn ($file) => \App\Support\AttachmentSaver::save($file))
                ->getUploadedAttachmentUrlUsing(fn ($file) => \App\Support\AttachmentSaver::url($file))
                ->maxLength(20000)
                ->columnSpanFull(),

            Forms\Components\Repeater::make('buttons')
                ->label('Кнопки')
                ->schema([
                    Forms\Components\TextInput::make('text')->label('Текст')->required()->maxLength(64),
                    Forms\Components\TextInput::make('url')->label('URL')->url()->maxLength(255),
                ])
                ->columns(2)
                ->addActionLabel('Добавить кнопку')
                ->dehydrateStateUsing(fn ($state) => self::buildButtonsJson($state))
                ->columnSpanFull(),
        ]);
    }

    /** Decode the JSON 'buttons' column into the array shape the Repeater expects. */
    public static function decodeButtonsForForm(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }
        $rows = [];
        foreach ($decoded as $btn) {
            $rows[] = [
                'text' => (string) ($btn['text'] ?? ''),
                'url'  => (string) ($btn['url']  ?? ''),
            ];
        }
        return $rows;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Заголовок')->searchable()->limit(60),
                Tables\Columns\TextColumn::make('alias')->label('Alias')->searchable(),
                Tables\Columns\TextColumn::make('pids')
                    ->label('Товаров')
                    ->formatStateUsing(static function ($state): string {
                        $arr = is_string($state) ? (json_decode($state, true) ?: []) : (array) $state;
                        return (string) count($arr);
                    }),
                Tables\Columns\TextColumn::make('views')->label('Просмотры')->numeric(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->formatStateUsing(fn ($state) => $state ? date('Y-m-d', (int) $state) : '—'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(static function (array $data): array {
                        // Convert the JSON 'buttons' column into the array
                        // shape the Repeater expects BEFORE Filament fills
                        // the form (avoids Repeater::getChildComponents()
                        // doing a foreach on a string).
                        $data['buttons'] = self::decodeButtonsForForm($data['buttons'] ?? null);
                        return $data;
                    })
                    ->mutateFormDataUsing(static function (array $data): array {
                        $data['updated_at'] = time();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageInstructions::route('/')];
    }

    /**
     * Кнопки из репитера → JSON-строка, как ожидает старый API.
     */
    public static function buildButtonsJson($state): string
    {
        $out = [];
        foreach ((array) $state as $btn) {
            $text = trim((string) ($btn['text'] ?? ''));
            $url = trim((string) ($btn['url'] ?? ''));
            if ($text === '') {
                continue;
            }
            $out[] = ['text' => $text, 'url' => $url];
        }
        return json_encode($out, JSON_UNESCAPED_UNICODE);
    }
}
