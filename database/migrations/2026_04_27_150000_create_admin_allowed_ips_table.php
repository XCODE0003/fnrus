<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dynamic IP allow-list managed from the admin UI (in addition to the
 * static one in env ADMIN_ALLOWED_IPS / config('admin.allowed_ips')).
 *
 * `expires_at` (unix ts, NULL = permanent) lets the main administrator
 * grant temporary access. Records past their expiry are ignored at
 * read time and pruned by the cleanup command.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `new_admin_allowed_ips` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cidr` varchar(45) NOT NULL,
  `note` varchar(255) NOT NULL DEFAULT '',
  `created_by` bigint(20) NOT NULL DEFAULT 0,
  `expires_at` int(25) NULL,
  `created_at` int(25) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_cidr` (`cidr`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_allowed_ips');
    }
};
