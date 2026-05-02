<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_shops_settings` (
`id` int(1) NOT NULL,
  `booking_time` int(2) NOT NULL,
  `ref_percent` int(3) NOT NULL,
  `count_products` int(1) NOT NULL,
  `count_categories` int(1) NOT NULL,
  `count_buttons_profile` int(1) NOT NULL,
  `display_products` int(1) NOT NULL,
  `min_sum_topup` float NOT NULL,
  `min_sum_withdrawal_card` float NOT NULL,
  `min_sum_withdrawal_balance` float NOT NULL,
  `orders_limit` int(20) NOT NULL,
  `notify_target_id` varchar(100) NOT NULL,
  `default_timezone` varchar(100) NOT NULL,
  `path_avatars` varchar(100) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `tg_notify_buys` int(1) NOT NULL,
  `tg_notify_balance` int(1) NOT NULL,
  `tg_notify_users` int(1) NOT NULL,
  `btn_tg_bot_url` varchar(255) DEFAULT 'https://t.me/Fanru_bot',
  `btn_tg_bot_text` varchar(255) DEFAULT 'Telegram Bot',
  `btn_reviews_url` varchar(255) DEFAULT 'https://t.me/Palkey',
  `btn_reviews_text` varchar(255) DEFAULT '@Palkey',
  `btn_buy_bot_url` varchar(255) DEFAULT 'https://t.me/Fanru_bot',
  `btn_buy_bot_text` varchar(255) DEFAULT 'Купить через бота',
  `policy_content_ru` mediumtext DEFAULT NULL,
  `policy_content_en` mediumtext DEFAULT NULL,
  `delivery_text_ru` text DEFAULT NULL,
  `delivery_text_en` text DEFAULT NULL,
  `btn_tg_bot_icon` varchar(50) DEFAULT 'telegram',
  `btn_buy_bot_icon` varchar(50) DEFAULT 'telegram',
  `btn_reviews_icon` varchar(50) DEFAULT 'telegram',
  `support_text` text DEFAULT NULL,
  `support_btn1_text` varchar(255) DEFAULT NULL,
  `support_btn1_url` varchar(255) DEFAULT NULL,
  `support_btn2_text` varchar(255) DEFAULT NULL,
  `support_btn2_url` varchar(255) DEFAULT NULL,
  `support_btn3_text` varchar(255) DEFAULT NULL,
  `support_btn3_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('shops_settings');
    }
};