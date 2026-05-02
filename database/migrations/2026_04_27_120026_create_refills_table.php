<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_refills` (
`id` int(25) NOT NULL AUTO_INCREMENT,
  `sid` int(100) NOT NULL,
  `owner_id` bigint(55) NOT NULL,
  `user_id` bigint(55) NOT NULL,
  `sum` float NOT NULL,
  `created_at` int(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('refills');
    }
};