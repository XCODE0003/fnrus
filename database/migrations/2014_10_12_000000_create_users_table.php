<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_users` (
  `id` int(50) NOT NULL AUTO_INCREMENT,
  `rid` bigint(55) NOT NULL,
  `tid` bigint(60) NOT NULL,
  `sid` int(200) NOT NULL,
  `mid` bigint(60) NOT NULL,
  `email` varchar(100) NOT NULL,
  `yandex_id` bigint(20) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tz` varchar(255) NOT NULL,
  `locale` varchar(2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_code` varchar(64) NOT NULL,
  `balance_main` float NOT NULL,
  `balance_affiliate` float NOT NULL,
  `ref_percent` int(3) NOT NULL,
  `ref_code` varchar(100) NOT NULL,
  `role_id` int(1) NOT NULL,
  `is_ban` int(1) NOT NULL,
  `is_active` int(1) NOT NULL,
  `email_notify_tickets` int(1) NOT NULL,
  `email_notify_orders` int(1) NOT NULL,
  `email_notify_status_changed` int(1) NOT NULL,
  `is_agreement` int(1) NOT NULL,
  `tstep` int(11) NOT NULL,
  `tdata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`tdata`)),
  `created_at` int(25) NOT NULL,
  PRIMARY KEY (`id`,`tid`,`sid`) USING BTREE,
  KEY `idx_yandex_id` (`yandex_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
