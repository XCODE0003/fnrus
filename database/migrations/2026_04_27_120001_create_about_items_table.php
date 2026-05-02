<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_about_items` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `icon` text DEFAULT NULL,
  `label_ru` varchar(255) NOT NULL DEFAULT '',
  `label_en` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(500) NOT NULL DEFAULT '',
  `url_text` varchar(255) NOT NULL DEFAULT '',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('about_items');
    }
};