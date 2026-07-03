<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-configurable "re-prompt 2FA every N hours" period. Drives the TTL of
 * the 2fa_passed marker in FilamentTwoFactorChallenge / RequireTwoFactor
 * (was hard-coded to 12h) via App\Providers\AppServiceProvider.
 */
return new class extends Migration {
    private const DEFAULT_HOURS = 12;

    public function up(): void
    {
        if (!Schema::hasTable('shops_settings')) return;

        Schema::table('shops_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('shops_settings', 'two_factor_ttl_hours')) {
                $table->unsignedSmallInteger('two_factor_ttl_hours')->nullable();
            }
        });

        DB::table('shops_settings')
            ->whereNull('two_factor_ttl_hours')
            ->update(['two_factor_ttl_hours' => self::DEFAULT_HOURS]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('shops_settings')) return;

        Schema::table('shops_settings', function (Blueprint $table) {
            if (Schema::hasColumn('shops_settings', 'two_factor_ttl_hours')) {
                $table->dropColumn('two_factor_ttl_hours');
            }
        });
    }
};
