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
     * Sync materials with the new order status:
     *  - 1|2 → 3|4 (terminal): return every pinned material to the pool
     *    (status 2|4 → 1, oid cleared) and restore product.count_all.
     *  - !2 → 2 (paid): mark reserved-or-available materials as sold so
     *    the buyer's /delivery/{hash} page actually receives a key.
     *    Without this, admins flipping an order to "Оплачен" by hand
     *    leave the key reserved and the customer sees an empty modal.
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
            return;
        }

        if ($newStatus === 2 && $prev !== 2) {
            $count = max(1, (int) ($this->record->count_all ?? 1));
            $sold = Material::markSoldForOrder(
                (int) $this->record->pid,
                (int) $this->record->tid,
                (int) $this->record->id,
                $count,
            );

            if ($sold > 0 && empty($this->record->payment_at)) {
                $this->record->forceFill(['payment_at' => time()])->save();
            }

            Log::info('order_status_transition_mark_sold', [
                'order_id' => $this->record->id,
                'from'     => $prev,
                'to'       => $newStatus,
                'requested'=> $count,
                'sold'     => $sold,
            ]);
        }
    }
}
