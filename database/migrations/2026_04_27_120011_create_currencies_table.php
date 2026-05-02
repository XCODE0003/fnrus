<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_currencies` (
`id` varchar(3) NOT NULL,
  `usd` float NOT NULL,
  `rub` float NOT NULL,
  `azn` float NOT NULL,
  `uah` float NOT NULL,
  `kzt` float NOT NULL,
  `uzs` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};