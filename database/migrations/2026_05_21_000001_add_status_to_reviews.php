<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE `new_reviews` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 0");
        // Существующие отзывы уже показывались на сайте — оставляем их опубликованными.
        DB::statement("UPDATE `new_reviews` SET `status` = 1");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `new_reviews` DROP COLUMN `status`");
    }
};
