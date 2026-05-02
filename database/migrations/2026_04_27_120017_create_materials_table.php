<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_materials` (
`id` int(200) NOT NULL AUTO_INCREMENT,
  `sid` int(200) NOT NULL,
  `pid` int(200) NOT NULL,
  `tid` int(100) NOT NULL,
  `eid` int(200) NOT NULL,
  `oid` int(200) NOT NULL,
  `bid` bigint(60) NOT NULL,
  `body` text NOT NULL,
  `status` int(1) NOT NULL,
  `created_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};