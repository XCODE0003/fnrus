<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Material;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /** @var int|null status before save — set in beforeSave so afterSave can compare. */
    private ?int $previousStatus = null;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->previousStatus = (int) $this->record->getOriginal('status');
    }

    /**
     * Whenever an order moves into a terminal state — "Отменен" (3) or
     * "Истек срок" (4) — release every material that points at that
     * order back to the warehouse: reserved (status=4) ones from a
     * pending order AND issued (status=2) ones from a paid order.
     *
     * Also bumps the product's stock counter to match.
     */
    protected function afterSave(): void
    {
        $newStatus = (int) $this->record->status;
        $prev = (int) ($this->previousStatus ?? 0);

        $isExitingActive = in_array($prev, [1, 2], true);
        $isEnteringTerminal = in_array($newStatus, [3, 4], true);

        if ($isExitingActive && $isEnteringTerminal) {
            $returned = Material::returnToStockFromOrder((int) $this->record->id);
            if ($returned > 0 && $this->record->pid > 0) {
                Product::where('id', $this->record->pid)->increment('count_all', $returned);
            }
            Log::info('order_status_transition_release', [
                'order_id' => $this->record->id,
                'from'     => $prev,
                'to'       => $newStatus,
                'returned' => $returned,
            ]);
        }
    }
}
