<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_shops` (
`id` int(200) NOT NULL AUTO_INCREMENT,
  `tid` bigint(60) NOT NULL,
  `username` varchar(255) NOT NULL,
  `token` text NOT NULL,
  `status` int(1) NOT NULL,
  `updated_at` int(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};