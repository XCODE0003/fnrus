<?php

declare(strict_types=1);

namespace App\Filament\Resources\InstructionResource\Pages;

use App\Filament\Resources\InstructionResource;
use App\Models\Instruction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageInstructions extends ManageRecords
{
    protected static string $resource = InstructionResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(static function (array $data): array {
                    $buttons = InstructionResource::buildButtonsJson($data['buttons_data'] ?? null);
                    unset($data['buttons_data']);
                    return array_merge($data, [
                        'buttons' => $buttons,
                        'views' => 0,
                        'updated_at' => time(),
                    ]);
                })
                ->before(function (array $data): void {
                    $alias = (string) ($data['alias'] ?? '');
                    if ($alias !== '' && Instruction::where('alias', $alias)->exists()) {
                        Notification::make()
                            ->title('Такой короткий адрес уже существует.')
                            ->danger()
                            ->send();
                        $this->halt();
                    }
                }),
        ];
    }
}
