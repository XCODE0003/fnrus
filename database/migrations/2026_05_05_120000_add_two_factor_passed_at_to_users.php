<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Persist the timestamp of the last successful TOTP verification on the
 * user row so the 12-hour 2FA grace period survives Laravel session GC
 * (SESSION_LIFETIME = 120 min) and the JWT-based session-rebuild path
 * inside AdminWebGuard.
 */
return new class extends Migration {
    public function up(): void
    {
        $cols = collect(DB::select('SHOW COLUMNS FROM `new_users`'))->pluck('Field')->all();
        if (!in_array('two_factor_passed_at', $cols, true)) {
            DB::statement('ALTER TABLE `new_users` ADD `two_factor_passed_at` INT(25) NULL');
        }
    }

    public function down(): void
    {
        $cols = collect(DB::select('SHOW COLUMNS FROM `new_users`'))->pluck('Field')->all();
        if (in_array('two_factor_passed_at', $cols, true)) {
            DB::statement('ALTER TABLE `new_users` DROP `two_factor_passed_at`');
        }
    }
};
