<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_coupons_uses` (
`promo_id` int(200) NOT NULL,
  `chat_id` bigint(60) NOT NULL,
  `shop_id` int(200) NOT NULL,
  `created_at` int(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons_uses');
    }
};