<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_withdrawal` (
`id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(60) NOT NULL,
  `sid` int(11) NOT NULL,
  `sum` float NOT NULL,
  `card_number` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `status` int(11) NOT NULL,
  `method` int(11) NOT NULL,
  `source` int(1) NOT NULL,
  `updated_at` varchar(40) NOT NULL,
  `created_at` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal');
    }
};