<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_materials_exports` (
`id` int(200) NOT NULL AUTO_INCREMENT,
  `sid` int(200) NOT NULL,
  `tid` int(100) NOT NULL,
  `pid` int(200) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_tariff` varchar(255) NOT NULL,
  `is_stock` int(1) NOT NULL,
  `count_all` int(200) NOT NULL,
  `created_at` int(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('materials_exports');
    }
};