<?php

declare(strict_types=1);

namespace App\Filament\Resources\SenderResource\Pages;

use App\Filament\Resources\SenderResource;
use App\Models\Member;
use App\Models\Shop;
use Filament\Resources\Pages\CreateRecord;

class CreateSender extends CreateRecord
{
    protected static string $resource = SenderResource::class;
    protected static ?string $title = 'Новая рассылка';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $now = time();
        $shop = Shop::getDefault();

        $typeTime = (int) ($data['type_time'] ?? 0);
        $scheduledAt = $data['scheduled_at'] ?? null;
        unset($data['type_time'], $data['scheduled_at']);

        // type_time=0 → запуск через минуту, 1 → выбранное время.
        $startedAt = $typeTime === 1 && $scheduledAt
            ? strtotime($scheduledAt)
            : ($now + 60);

        // buttons_data → JSON; пустые text/url выкидываем.
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

        return array_merge($data, [
            'sid' => $shop?->id ?? 0,
            'title' => $data['title'] ?? 'Без названия',
            'type' => (int) ($data['type'] ?? 1),
            'message' => $data['message'] ?? '',
            'forward_link' => $data['forward_link'] ?? '',
            'image' => $data['image'] ?? '',
            'disable_web_page_preview' => (int) ($data['disable_web_page_preview'] ?? 0),
            'has_spoiler' => (int) ($data['has_spoiler'] ?? 0),
            'count_all' => Member::query()->count(),
            'count_success' => 0,
            'count_fail' => 0,
            'status' => 1,
            'started_at' => $startedAt,
            'updated_at' => $now,
            'created_at' => $now,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
