<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AttachResource\Pages;
use App\Models\Attach;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachResource extends Resource
{
    protected static ?string $model = Attach::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';
    protected static ?string $navigationGroup = 'Контент';
    protected static ?string $navigationLabel = 'Файлы / вложения';
    protected static ?string $modelLabel = 'файл';
    protected static ?string $pluralModelLabel = 'файлы';
    protected static ?int $navigationSort = 13;

    private const TYPE_LABELS = [
        0 => 'Обложка',
        1 => 'Файл',
        2 => 'Аватар',
    ];

    /** Mirrors AttachmentController::uploadFiles. */
    private const ALLOWED_EXTENSIONS = ['mp3', 'wav', 'mp4', 'flv', 'zip', 'rar', 'pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const MAX_FILE_KB = 500_000;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Имя')->disabled(),
            Forms\Components\TextInput::make('ext')->label('Расширение')->disabled(),
            Forms\Components\TextInput::make('size')->label('Размер (байт)')->numeric()->disabled(),
            Forms\Components\TextInput::make('type')->label('Тип')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->copyMessage('ID скопирован'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Имя')
                    ->searchable()
                    ->wrap()
                    ->limit(60),

                Tables\Columns\TextColumn::make('ext')
                    ->label('Ext')
                    ->badge(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => self::TYPE_LABELS[(int) $state] ?? (string) $state),

                Tables\Columns\TextColumn::make('size')
                    ->label('Размер')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => self::formatBytes((int) $state)),

                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label('Загружен')
                    ->formatStateUsing(fn ($state) => $state ? date('d.m.Y H:i', (int) $state) : '—')
                    ->sortable(),

                // Public URL — copy-to-clipboard на клик по значению.
                Tables\Columns\TextColumn::make('public_url')
                    ->label('Ссылка')
                    ->state(fn (Attach $record) => self::publicUrl($record))
                    ->limit(30)
                    ->tooltip(fn (Attach $record) => self::publicUrl($record))
                    ->copyable()
                    ->copyMessage('Ссылка скопирована')
                    ->copyMessageDuration(1500)
                    ->icon('heroicon-o-link')
                    ->iconPosition('after'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options(self::TYPE_LABELS),
            ])
            ->headerActions([
                Tables\Actions\Action::make('upload')
                    ->label('Загрузить файл')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->modalHeading('Загрузка файла')
                    ->modalSubmitActionLabel('Загрузить')
                    ->form([
                        Forms\Components\FileUpload::make('files')
                            ->label('Файл(ы)')
                            ->required()
                            ->multiple()
                            ->disk('local')
                            ->directory('tmp/filament-uploads')
                            ->preserveFilenames()
                            ->maxSize(self::MAX_FILE_KB)
                            ->helperText('Допустимые форматы: ' . implode(', ', self::ALLOWED_EXTENSIONS) . '. До ' . round(self::MAX_FILE_KB / 1024) . ' MB на файл.'),
                    ])
                    ->action(function (array $data): void {
                        $files = $data['files'] ?? [];
                        if (! is_array($files)) {
                            $files = [$files];
                        }

                        $ok = 0;
                        $errors = [];
                        foreach ($files as $tmpPath) {
                            try {
                                self::ingestUploadedFile((string) $tmpPath);
                                $ok++;
                            } catch (\Throwable $e) {
                                $errors[] = basename((string) $tmpPath) . ': ' . $e->getMessage();
                            }
                        }

                        if ($ok > 0) {
                            Notification::make()
                                ->success()
                                ->title('Загружено: ' . $ok)
                                ->body($errors ? 'С ошибками: ' . count($errors) : null)
                                ->send();
                        }
                        if ($errors) {
                            Notification::make()
                                ->danger()
                                ->title('Ошибки загрузки')
                                ->body(implode("\n", array_slice($errors, 0, 5)))
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Открыть')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->url(fn (Attach $record) => self::publicUrl($record), shouldOpenInNewTab: true),

                Tables\Actions\DeleteAction::make()
                    ->before(function (Attach $record): void {
                        $path = 'files/' . $record->id . '.' . $record->ext;
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records): void {
                        foreach ($records as $record) {
                            $path = 'files/' . $record->id . '.' . $record->ext;
                            if (Storage::disk('public')->exists($path)) {
                                Storage::disk('public')->delete($path);
                            }
                        }
                    }),
            ])
            ->defaultSort('uploaded_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAttaches::route('/')];
    }

    /**
     * Mirror of AttachmentController::uploadFiles, adapted for Filament's FileUpload.
     * The temp file lives on the 'local' disk; we move it to public/files/{id}.{ext}
     * and insert the row.
     */
    private static function ingestUploadedFile(string $tmpPath): void
    {
        if ($tmpPath === '') {
            throw new \RuntimeException('Файл не получен');
        }

        if (! Storage::disk('local')->exists($tmpPath)) {
            throw new \RuntimeException('Временный файл не найден');
        }

        $originalName = basename($tmpPath);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            Storage::disk('local')->delete($tmpPath);
            throw new \RuntimeException('Недопустимое расширение: ' . $extension);
        }

        $size = (int) Storage::disk('local')->size($tmpPath);

        // Random 10-char id, как в старом контроллере.
        do {
            $id = Str::random(10);
        } while (Attach::query()->where('id', $id)->exists());

        $targetPath = 'files/' . $id . '.' . $extension;

        $stream = Storage::disk('local')->readStream($tmpPath);
        if ($stream === false || $stream === null) {
            throw new \RuntimeException('Не удалось открыть временный файл');
        }
        Storage::disk('public')->writeStream($targetPath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        Storage::disk('local')->delete($tmpPath);

        Attach::query()->create([
            'id' => $id,
            'uid' => Auth::id() ?? 0,
            'title' => $originalName,
            'ext' => $extension,
            'size' => $size,
            'type' => 1,
            'uploaded_at' => time(),
        ]);
    }

    /** Public URL served by StorageController::file (route /file/{hash}). */
    public static function publicUrl(Attach $record): string
    {
        return url('/file/' . $record->id);
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        return number_format($value, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }
}
