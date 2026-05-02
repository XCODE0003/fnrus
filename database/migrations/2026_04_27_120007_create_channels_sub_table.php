<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `new_channels_sub` (
`sid` bigint(70) NOT NULL,
  `cid` bigint(70) NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `link` varchar(255) NOT NULL,
  `is_active` int(1) NOT NULL,
  `sort` int(1) NOT NULL,
  `created_at` int(25) NOT NULL,
  PRIMARY KEY (`sid`,`cid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('channels_sub');
    }
};