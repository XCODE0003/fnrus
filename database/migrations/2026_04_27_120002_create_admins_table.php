<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_admins` (
`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` int(200) NOT NULL,
  `tid` bigint(60) NOT NULL,
  `sid` int(200) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tz` varchar(300) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `is_ban` int(1) NOT NULL,
  `is_adm` int(1) NOT NULL,
  `is_policy` int(1) NOT NULL,
  `role_id` int(1) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};