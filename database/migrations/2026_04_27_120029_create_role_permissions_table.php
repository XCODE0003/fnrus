<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_role_permissions` (
`id` int(1) NOT NULL AUTO_INCREMENT,
  `role_id` int(1) NOT NULL,
  `title` varchar(255) NOT NULL,
  `permission` varchar(255) NOT NULL,
  `allow` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};