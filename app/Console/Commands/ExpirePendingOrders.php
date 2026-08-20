<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Expire overdue pending orders and release reserved stock';

    public function handle(): int
    {
        $expired = Order::expirePending();

        $this->info(sprintf('Expired %d pending order(s).', $expired->count()));

        return self::SUCCESS;
    }
}
