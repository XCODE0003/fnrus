<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_shops_payment_assets` (
`id` int(10) NOT NULL AUTO_INCREMENT,
  `psid` int(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `min` float NOT NULL,
  `max` float NOT NULL,
  `currency` varchar(10) NOT NULL,
  `code` varchar(10) NOT NULL,
  `active` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('shops_payment_assets');
    }
};