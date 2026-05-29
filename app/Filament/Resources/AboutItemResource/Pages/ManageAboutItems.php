<?php

declare(strict_types=1);

namespace App\Filament\Resources\AboutItemResource\Pages;

use App\Filament\Resources\AboutItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAboutItems extends ManageRecords
{
    protected static string $resource = AboutItemResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(static function (array $data): array {
                    $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
                    return $data;
                }),
        ];
    }
}
