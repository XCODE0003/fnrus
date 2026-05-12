<?php

declare(strict_types=1);

namespace App\Filament\Resources\SenderResource\Pages;

use App\Filament\Resources\SenderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSender extends EditRecord
{
    protected static string $resource = SenderResource::class;
    protected static ?string $title = 'Редактирование рассылки';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Пред-заполнение: если started_at в будущем → «Позже» с этим временем.
        $startedAt = (int) ($data['started_at'] ?? 0);
        if ($startedAt > time()) {
            $data['type_time'] = 1;
            $data['scheduled_at'] = date('Y-m-d H:i', $startedAt);
        } else {
            $data['type_time'] = 0;
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $now = time();
        $typeTime = (int) ($data['type_time'] ?? 0);
        $scheduledAt = $data['scheduled_at'] ?? null;
        unset($data['type_time'], $data['scheduled_at']);

        $startedAt = $typeTime === 1 && $scheduledAt
            ? strtotime($scheduledAt)
            : ($now + 60);

        $buttons = [];
        foreach ((array) ($data['buttons_data'] ?? []) as $b) {
            $text = trim((string) ($b['text'] ?? ''));
            $url = trim((string) ($b['url'] ?? ''));
            if ($text === '') {
                continue;
            }
            $buttons[] = $url !== ''
                ? ['text' => $text, 'url' => $url]
                : ['text' => $text, 'callback_data' => $text];
        }
        unset($data['buttons_data']);
        $data['buttons'] = json_encode($buttons, JSON_UNESCAPED_UNICODE);
        $data['started_at'] = $startedAt;
        $data['updated_at'] = $now;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
