<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_links_ads_users` (
`id` bigint(70) NOT NULL,
  `link_id` int(100) NOT NULL,
  `created_at` int(25) NOT NULL,
  PRIMARY KEY (`id`,`link_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('links_ads_users');
    }
};