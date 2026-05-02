<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_attachments` (
`id` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `uid` int(200) NOT NULL,
  `ext` varchar(10) NOT NULL,
  `size` int(11) NOT NULL,
  `type` int(1) NOT NULL,
  `uploaded_at` int(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};