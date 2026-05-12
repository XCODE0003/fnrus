<?php

declare(strict_types=1);

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqs extends ManageRecords
{
    protected static string $resource = FaqResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(static function (array $data): array {
                    $data['in_id'] = (int) ($data['in_id'] ?? 0);
                    $data['visibility'] = (int) ($data['visibility'] ?? 1);
                    $data['sort'] = (int) ($data['sort'] ?? 0);
                    $data['updated_at'] = time();
                    return $data;
                }),
        ];
    }
}
