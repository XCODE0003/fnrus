<?php

declare(strict_types=1);

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Models\Coupon;
use App\Models\Shop;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageCoupons extends ManageRecords
{
    protected static string $resource = CouponResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(static function (array $data): array {
                    $shop = Shop::getDefault();
                    $now = time();
                    return array_merge($data, [
                        'sid' => $shop?->id ?? 0,
                        'count_expired' => 0,
                        'count_expired_type' => 0,
                        'is_new_users' => (int) ($data['is_new_users'] ?? 0),
                        'is_one_time' => (int) ($data['is_one_time'] ?? 0),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                })
                ->before(function (array $data): void {
                    $shop = Shop::getDefault();
                    if ($shop && Coupon::where('sid', $shop->id)->where('code', $data['code'] ?? '')->exists()) {
                        Notification::make()
                            ->title('Такой промокод уже существует.')
                            ->danger()
                            ->send();
                        $this->halt();
                    }
                }),
        ];
    }
}
