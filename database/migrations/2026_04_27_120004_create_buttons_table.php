<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_buttons` (
`id` int(100) NOT NULL AUTO_INCREMENT,
  `sid` bigint(55) NOT NULL,
  `title` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `disable_web_page_preview` int(1) NOT NULL,
  `image_spoiler` int(1) NOT NULL,
  `buttons` longtext NOT NULL,
  `type` int(11) NOT NULL,
  `sort` int(10) NOT NULL,
  `visible` int(1) NOT NULL,
  `updated_at` int(25) NOT NULL,
  `created_at` int(25) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('buttons');
    }
};