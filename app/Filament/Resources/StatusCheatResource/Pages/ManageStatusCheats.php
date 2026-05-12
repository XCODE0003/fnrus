<?php

namespace App\Filament\Resources\StatusCheatResource\Pages;

use App\Filament\Resources\StatusCheatResource;
use App\Models\StatusCheat;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStatusCheats extends ManageRecords
{
    protected static string $resource = StatusCheatResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data): StatusCheat {
                    unset($data['is_notify']);
                    $data['updated_at'] = strtotime('NOW');
                    return StatusCheat::create($data);
                }),
        ];
    }
}
