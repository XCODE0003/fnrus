<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_payment_systems` (
`id` int(200) NOT NULL AUTO_INCREMENT,
  `pid` int(1) NOT NULL,
  `title` varchar(255) NOT NULL,
  `link` varchar(100) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `type` varchar(3) NOT NULL,
  `active` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_systems');
    }
};