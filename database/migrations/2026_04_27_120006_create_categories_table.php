<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_categories` (
`id` int(200) NOT NULL AUTO_INCREMENT,
  `cid` int(200) NOT NULL,
  `sid` int(200) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `seo_description` text NOT NULL,
  `seo_keywords` text NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `image_site` varchar(255) NOT NULL,
  `image_spoiler` int(11) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `disable_web_page_preview` int(1) NOT NULL,
  `display_products` int(1) NOT NULL,
  `count_views` int(200) NOT NULL,
  `count_column` int(11) NOT NULL,
  `sort` int(200) NOT NULL,
  `visibility` int(1) NOT NULL,
  `updated_at` int(30) NOT NULL,
  `created_at` int(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};