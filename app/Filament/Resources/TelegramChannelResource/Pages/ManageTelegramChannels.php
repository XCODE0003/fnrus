<?php

declare(strict_types=1);

namespace App\Filament\Resources\TelegramChannelResource\Pages;

use App\Filament\Resources\TelegramChannelResource;
use App\Models\Shop;
use App\Models\TelegramChannel;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTelegramChannels extends ManageRecords
{
    protected static string $resource = TelegramChannelResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data): TelegramChannel {
                    $shop = Shop::getDefault();
                    $now = time();
                    $data['sid'] = $shop?->id ?? 1;
                    $data['created_at'] = $now;
                    $data['updated_at'] = $now;
                    return TelegramChannel::create($data);
                }),
        ];
    }
}
