<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_tickets` (
`id` int(25) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(60) NOT NULL,
  `operator_id` int(100) NOT NULL,
  `subject_id` int(3) NOT NULL,
  `status` int(1) NOT NULL,
  `last_answer_at` int(25) NOT NULL,
  `expired_at` int(25) NOT NULL,
  `created_at` int(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};