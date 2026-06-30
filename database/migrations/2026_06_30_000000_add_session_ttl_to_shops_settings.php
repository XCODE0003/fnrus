<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-configurable storefront session length (in days). Drives both the
 * JWT TTL (App\Providers\AppServiceProvider overrides config('jwt.ttl'))
 * and the session_token cookie lifetime (window.SESSION_TTL_DAYS).
 */
return new class extends Migration {
    private const DEFAULT_DAYS = 30;

    public function up(): void
    {
        if (!Schema::hasTable('shops_settings')) return;

        Schema::table('shops_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('shops_settings', 'session_ttl_days')) {
                $table->unsignedSmallInteger('session_ttl_days')->nullable();
            }
        });

        DB::table('shops_settings')
            ->whereNull('session_ttl_days')
            ->update(['session_ttl_days' => self::DEFAULT_DAYS]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('shops_settings')) return;

        Schema::table('shops_settings', function (Blueprint $table) {
            if (Schema::hasColumn('shops_settings', 'session_ttl_days')) {
                $table->dropColumn('session_ttl_days');
            }
        });
    }
};
