<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_texts` (
`id` int(25) NOT NULL AUTO_INCREMENT,
  `sid` int(25) NOT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(100) NOT NULL,
  `disable_web_page_preview` int(1) NOT NULL,
  `is_spoiler` int(1) NOT NULL,
  `buttons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`buttons`)),
  `type` varchar(50) NOT NULL,
  `is_active` int(1) NOT NULL,
  `updated_at` int(25) NOT NULL,
  `created_at` int(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('texts');
    }
};