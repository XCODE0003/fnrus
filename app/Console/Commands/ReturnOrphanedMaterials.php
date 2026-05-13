<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-shot housekeeping: every order that is currently in a terminal
 * state (Отменен / Истек срок) but still has materials pinned to it
 * gets its keys released back to the warehouse and the product's
 * stock counter restored.
 *
 * Run with `--dry-run` first to see what would change. Idempotent —
 * once a material is returned (status=1, oid=0) it is not touched
 * again on subsequent runs.
 */
class ReturnOrphanedMaterials extends Command
{
    protected $signature = 'materials:return-orphaned {--dry-run : Show what would change without writing}';

    protected $description = 'Return materials still pinned to canceled/expired orders back to the warehouse pool';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $terminalOrderIds = Order::query()
            ->whereIn('status', [3, 4])
            ->pluck('id')
            ->all();

        if (! $terminalOrderIds) {
            $this->info('No canceled/expired orders found.');
            return self::SUCCESS;
        }

        $stuck = Material::query()
            ->whereIn('oid', $terminalOrderIds)
            ->whereIn('status', [2, 4])
            ->select(['pid', DB::raw('COUNT(*) as cnt')])
            ->groupBy('pid')
            ->get();

        if ($stuck->isEmpty()) {
            $this->info('Nothing to return — every terminal order is already cleaned up.');
            return self::SUCCESS;
        }

        $totalReturned = 0;

        foreach ($stuck as $row) {
            $pid = (int) $row->pid;
            $cnt = (int) $row->cnt;

            $this->line(sprintf('Product %d: %s %d material(s)', $pid, $dry ? 'would return' : 'returning', $cnt));

            if ($dry) {
                $totalReturned += $cnt;
                continue;
            }

            $affected = Material::query()
                ->whereIn('oid', $terminalOrderIds)
                ->where('pid', $pid)
                ->whereIn('status', [2, 4])
                ->update(['status' => 1, 'oid' => 0, 'bid' => 0]);

            if ($affected > 0 && $pid > 0) {
                Product::where('id', $pid)->increment('count_all', $affected);
            }

            $totalReturned += $affected;
        }

        $this->info(sprintf('%s %d material(s) total.', $dry ? 'Would return' : 'Returned', $totalReturned));

        return self::SUCCESS;
    }
}
