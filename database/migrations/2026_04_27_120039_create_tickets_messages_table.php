<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_tickets_messages` (
`id` int(25) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(55) NOT NULL,
  `operator_id` int(200) NOT NULL,
  `ticket_id` int(25) NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) NOT NULL,
  `is_read` int(1) NOT NULL,
  `created_at` int(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets_messages');
    }
};