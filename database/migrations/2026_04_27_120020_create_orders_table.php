<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_orders` (
`id` int(200) NOT NULL AUTO_INCREMENT,
  `sid` int(200) NOT NULL,
  `pid` int(200) NOT NULL,
  `tid` int(100) NOT NULL,
  `bid` bigint(60) NOT NULL,
  `oid` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` float NOT NULL,
  `amount_rub` float NOT NULL,
  `currency` varchar(3) NOT NULL,
  `count_all` int(200) NOT NULL,
  `promo_id` int(11) NOT NULL,
  `method_payment` varchar(2) NOT NULL,
  `is_sent` int(11) NOT NULL,
  `msg_id` bigint(20) NOT NULL,
  `status` int(1) NOT NULL,
  `hash` varchar(255) NOT NULL,
  `delivery_hash` varchar(100) NOT NULL,
  `type` int(11) NOT NULL,
  `payment_at` int(30) NOT NULL,
  `expired_at` int(30) NOT NULL,
  `created_at` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};