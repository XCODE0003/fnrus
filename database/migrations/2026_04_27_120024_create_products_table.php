<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_products` (
`id` int(200) NOT NULL AUTO_INCREMENT,
  `cid` int(200) NOT NULL,
  `sid` int(200) NOT NULL,
  `title` varchar(255) NOT NULL,
  `seo_description` text NOT NULL,
  `seo_keywords` text NOT NULL,
  `description` text NOT NULL,
  `advantages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`advantages`)),
  `image` varchar(255) NOT NULL,
  `image_site` varchar(255) NOT NULL,
  `gallery` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`gallery`)),
  `functional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`functional`)),
  `image_spoiler` int(11) NOT NULL,
  `alias` varchar(255) NOT NULL,
  `link_video` varchar(255) NOT NULL,
  `system_platforms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`system_platforms`)),
  `system_versions` varchar(255) NOT NULL,
  `system_auth` varchar(255) NOT NULL,
  `status_id` int(100) NOT NULL,
  `disable_web_page_preview` int(1) NOT NULL,
  `price` float NOT NULL,
  `price_type` int(1) NOT NULL,
  `currency` int(1) NOT NULL,
  `status` int(1) NOT NULL,
  `count_views` int(200) NOT NULL,
  `count_min` int(200) NOT NULL,
  `count_max` int(200) NOT NULL,
  `count_sales` int(200) NOT NULL,
  `count_all` int(200) NOT NULL,
  `count_type` int(1) NOT NULL,
  `is_endless` int(1) NOT NULL,
  `is_root` int(1) NOT NULL,
  `is_jailbreak` int(1) NOT NULL,
  `hack_status` int(1) NOT NULL,
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
        Schema::dropIfExists('products');
    }
};