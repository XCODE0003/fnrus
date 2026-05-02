<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_shops_payment_methods` (
`sid` int(200) NOT NULL,
  `psid` int(10) NOT NULL,
  `pid` int(200) NOT NULL,
  `public_id` varchar(100) NOT NULL,
  `public_key` text NOT NULL,
  `secret_key` text NOT NULL,
  `secret_key_two` text NOT NULL,
  `theme_code` varchar(100) NOT NULL,
  `assets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`assets`)),
  `type` varchar(100) NOT NULL,
  `active` int(1) NOT NULL,
  `updated_at` int(25) NOT NULL,
  PRIMARY KEY (`psid`,`sid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('shops_payment_methods');
    }
};