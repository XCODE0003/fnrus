<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add 2FA columns to legacy new_users table.
        $columns = collect(DB::select('SHOW COLUMNS FROM `new_users`'))->pluck('Field')->all();

        $alters = [];
        if (!in_array('two_factor_secret', $columns, true)) {
            $alters[] = "ADD `two_factor_secret` TEXT NULL";
        }
        if (!in_array('two_factor_recovery_codes', $columns, true)) {
            $alters[] = "ADD `two_factor_recovery_codes` LONGTEXT NULL";
        }
        if (!in_array('two_factor_confirmed_at', $columns, true)) {
            $alters[] = "ADD `two_factor_confirmed_at` INT(25) NULL";
        }
        if (!empty($alters)) {
            DB::statement('ALTER TABLE `new_users` ' . implode(', ', $alters));
        }

        // Login attempt audit log.
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `new_login_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(500) NOT NULL DEFAULT '',
  `successful` tinyint(1) NOT NULL DEFAULT 0,
  `reason` varchar(100) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_created` (`ip`,`created_at`),
  KEY `idx_username_created` (`username`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM `new_users`'))->pluck('Field')->all();
        $drops = [];
        foreach (['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'] as $col) {
            if (in_array($col, $columns, true)) {
                $drops[] = "DROP `$col`";
            }
        }
        if (!empty($drops)) {
            DB::statement('ALTER TABLE `new_users` ' . implode(', ', $drops));
        }

        Schema::dropIfExists('login_attempts');
    }
};
