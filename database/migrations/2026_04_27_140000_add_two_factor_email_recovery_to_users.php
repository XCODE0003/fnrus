<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add columns used by the email-based 2FA reset flow.
 *
 *   two_factor_recovery_email_code        — random 64-char one-time token
 *   two_factor_recovery_email_expires_at  — unix ts (validity window)
 *   two_factor_recovery_email_requested_at— unix ts (rate-limit anchor)
 *
 * The token is hashed before storage; only the hash is kept on the user
 * row. Hashing prevents read-DB → take-over scenarios.
 */
return new class extends Migration {
    public function up(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM `new_users`'))->pluck('Field')->all();
        $alters = [];
        if (!in_array('two_factor_recovery_email_code', $columns, true)) {
            $alters[] = "ADD `two_factor_recovery_email_code` VARCHAR(128) NULL";
        }
        if (!in_array('two_factor_recovery_email_expires_at', $columns, true)) {
            $alters[] = "ADD `two_factor_recovery_email_expires_at` INT(25) NULL";
        }
        if (!in_array('two_factor_recovery_email_requested_at', $columns, true)) {
            $alters[] = "ADD `two_factor_recovery_email_requested_at` INT(25) NULL";
        }
        if (!empty($alters)) {
            DB::statement('ALTER TABLE `new_users` ' . implode(', ', $alters));
        }
    }

    public function down(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM `new_users`'))->pluck('Field')->all();
        $drops = [];
        foreach (['two_factor_recovery_email_code', 'two_factor_recovery_email_expires_at', 'two_factor_recovery_email_requested_at'] as $col) {
            if (in_array($col, $columns, true)) {
                $drops[] = "DROP `$col`";
            }
        }
        if (!empty($drops)) {
            DB::statement('ALTER TABLE `new_users` ' . implode(', ', $drops));
        }
    }
};
