<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_senders` (
`id` int(200) NOT NULL AUTO_INCREMENT,
  `sid` int(200) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `disable_web_page_preview` int(1) NOT NULL,
  `has_spoiler` int(1) NOT NULL,
  `forward_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `buttons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `count_all` int(100) NOT NULL,
  `count_success` int(100) NOT NULL,
  `count_fail` int(100) NOT NULL,
  `type` int(1) NOT NULL,
  `status` int(1) NOT NULL,
  `started_at` int(25) NOT NULL,
  `updated_at` int(25) NOT NULL,
  `created_at` int(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('senders');
    }
};