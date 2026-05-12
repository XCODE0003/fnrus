<?php

namespace App\Filament\Resources\AttachResource\Pages;

use App\Filament\Resources\AttachResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAttaches extends ManageRecords
{
    protected static string $resource = AttachResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
