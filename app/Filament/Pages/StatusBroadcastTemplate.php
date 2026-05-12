<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\ShopSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StatusBroadcastTemplate extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Шаблон рассылки статусов';
    protected static ?string $title = 'Глобальный шаблон сообщения при изменении статуса';
    protected static ?int $navigationSort = 70;

    protected static string $view = 'filament.pages.status-broadcast-template';
    protected static ?string $slug = 'status-broadcast-template';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = ShopSettings::getDefault();
        $this->form->fill([
            'template' => $settings?->status_broadcast_template ?? '',
            'image_path' => $settings?->status_broadcast_image_path,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('hint')
                    ->label('Доступные плейсхолдеры')
                    ->content('{game}, {product}, {status} — будут подставлены автоматически. Используется как fallback, когда у конкретного статуса свой шаблон не задан.'),
                Forms\Components\RichEditor::make('template')
                    ->label('Текст сообщения')
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike',
                        'bulletList', 'orderedList', 'link', 'redo', 'undo',
                    ])
                    ->maxLength(8000)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image_path')
                    ->label('Картинка по умолчанию')
                    ->image()
                    ->directory('status-posts')
                    ->disk('public')
                    ->maxSize(5120)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = ShopSettings::getDefault();
        if ($settings === null) {
            Notification::make()
                ->danger()
                ->title('Не найдено shops_settings — настройка не сохранена')
                ->send();
            return;
        }

        $settings->status_broadcast_template = $data['template'] ?? '';
        $settings->status_broadcast_image_path = $data['image_path'] ?: null;
        $settings->save();

        Notification::make()
            ->success()
            ->title('Шаблон сохранён')
            ->send();
    }
}
