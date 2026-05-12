<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailBroadcastResource\Pages;

use App\Filament\Resources\EmailBroadcastResource;
use App\Models\EmailBroadcast;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ManageEmailBroadcasts extends ManageRecords
{
    protected static string $resource = EmailBroadcastResource::class;

    public function getSubheading(): string | Htmlable | null
    {
        $eligible = number_format(EmailBroadcastResource::audienceEligible(), 0, '.', ' ');
        $total = number_format(EmailBroadcastResource::audienceTotal(), 0, '.', ' ');
        $out = number_format(EmailBroadcastResource::audienceOptedOut(), 0, '.', ' ');

        return new HtmlString(
            '<span class="text-sm text-gray-500">'
            . 'Аудитория: <strong>' . $eligible . '</strong> из ' . $total
            . ' (' . $out . ' отписались)'
            . '</span>'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Новая рассылка')
                ->icon('heroicon-o-plus')
                ->using(function (array $data): EmailBroadcast {
                    $now = time();
                    $data['admin_id'] = Auth::id();
                    $data['status'] = EmailBroadcast::STATUS_DRAFT;
                    $data['created_at'] = $now;
                    $data['updated_at'] = $now;
                    return EmailBroadcast::create($data);
                }),
        ];
    }
}
