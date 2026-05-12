<?php

namespace App\Filament\Resources\LinkAdResource\Pages;

use App\Filament\Resources\LinkAdResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLinkAds extends ManageRecords
{
    protected static string $resource = LinkAdResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
