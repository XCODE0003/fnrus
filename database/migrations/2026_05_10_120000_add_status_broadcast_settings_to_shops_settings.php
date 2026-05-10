<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Global default for status-change broadcast: a single shop-wide message
 * template + image used when an individual status row leaves its own
 * `message_template` / `image_path` blank.
 */
return new class extends Migration {
    private const DEFAULT_TEMPLATE = "<p>Изменение статуса чита</p><p>├ Игра: <b>{game}</b></p><p>├ Чит: <b>{product}</b></p><p>└ Статус: <b>{status}</b></p>";

    public function up(): void
    {
        if (!Schema::hasTable('shops_settings')) return;

        Schema::table('shops_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('shops_settings', 'status_broadcast_template')) {
                $table->text('status_broadcast_template')->nullable();
            }
            if (!Schema::hasColumn('shops_settings', 'status_broadcast_image_path')) {
                $table->string('status_broadcast_image_path', 500)->nullable();
            }
        });

        // Seed default template only on rows where the column is still NULL.
        DB::table('shops_settings')
            ->whereNull('status_broadcast_template')
            ->update(['status_broadcast_template' => self::DEFAULT_TEMPLATE]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('shops_settings')) return;

        Schema::table('shops_settings', function (Blueprint $table) {
            foreach (['status_broadcast_template', 'status_broadcast_image_path'] as $col) {
                if (Schema::hasColumn('shops_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
