<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentSystemResource\Pages;

use App\Filament\Resources\PaymentSystemResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentSystems extends ManageRecords
{
    protected static string $resource = PaymentSystemResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
