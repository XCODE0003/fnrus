<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $col = DB::select("SHOW COLUMNS FROM `new_categories` LIKE 'image_hero'");
        if (empty($col)) {
            DB::statement("ALTER TABLE `new_categories` ADD COLUMN `image_hero` varchar(255) NOT NULL DEFAULT '' AFTER `image_site`");
        }
    }

    public function down(): void
    {
        $col = DB::select("SHOW COLUMNS FROM `new_categories` LIKE 'image_hero'");
        if (!empty($col)) {
            DB::statement("ALTER TABLE `new_categories` DROP COLUMN `image_hero`");
        }
    }
};
