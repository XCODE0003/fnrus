<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_coupons` (
`id` int(200) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL,
  `gids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `code` varchar(100) NOT NULL,
  `sale` int(100) NOT NULL,
  `sale_type` int(1) NOT NULL,
  `min_sum` float NOT NULL,
  `count_uses_min` int(20) NOT NULL,
  `count_uses_type` int(1) NOT NULL,
  `count_uses_max` int(200) NOT NULL,
  `count_expired` int(100) NOT NULL,
  `count_expired_type` int(1) NOT NULL,
  `is_new_users` int(1) NOT NULL,
  `is_one_time` int(1) NOT NULL,
  `updated_at` int(30) NOT NULL,
  `created_at` int(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};