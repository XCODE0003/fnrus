<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ТЗ §7 — every game gets an explicit platform ("ПК" / "Мобильные") set in the
 * admin, instead of the catalog guessing it from hard-coded child category
 * names ('пк', 'ios', 'android', 'gameloop', 'эмуляторы').
 *
 * Values: 'pc', 'mobile', or 'pc mobile' for a game available on both.
 * Existing rows are backfilled with the same guess the catalog used, so the
 * storefront keeps working before anyone opens the admin.
 */
return new class extends Migration {
    public function up(): void
    {
        $col = DB::select("SHOW COLUMNS FROM `new_categories` LIKE 'platform'");
        if (empty($col)) {
            DB::statement("ALTER TABLE `new_categories` ADD COLUMN `platform` varchar(20) NOT NULL DEFAULT '' AFTER `alias`");
        }

        // Backfill the root games from their children's titles (one-off).
        $games = DB::table('categories')->where('cid', 0)->get(['id']);
        foreach ($games as $game) {
            $children = DB::table('categories')->where('cid', $game->id)->pluck('title');

            $hasPc = false;
            $hasMobile = false;
            foreach ($children as $title) {
                $name = mb_strtolower(trim((string) $title));
                if (in_array($name, ['пк', 'pc', 'gameloop', 'эмуляторы'], true)) {
                    $hasPc = true;
                } elseif (in_array($name, ['ios', 'android'], true)) {
                    $hasMobile = true;
                }
            }

            $platforms = [];
            if ($hasPc) { $platforms[] = 'pc'; }
            if ($hasMobile) { $platforms[] = 'mobile'; }
            if (!$platforms) { $platforms = ['pc', 'mobile']; }

            DB::table('categories')->where('id', $game->id)->update([
                'platform' => implode(' ', $platforms),
            ]);
        }
    }

    public function down(): void
    {
        $col = DB::select("SHOW COLUMNS FROM `new_categories` LIKE 'platform'");
        if (!empty($col)) {
            DB::statement("ALTER TABLE `new_categories` DROP COLUMN `platform`");
        }
    }
};
