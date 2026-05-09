<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds force-password-reset fields to the users table. When set, the
 * login flow rejects the user until they complete the reset via the
 * one-time token sent to their email.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'force_password_reset')) {
                $table->unsignedTinyInteger('force_password_reset')->default(0)->index()->after('password');
            }
            if (!Schema::hasColumn('users', 'password_reset_token')) {
                $table->string('password_reset_token', 64)->nullable()->unique()->after('force_password_reset');
            }
            if (!Schema::hasColumn('users', 'password_reset_expires_at')) {
                $table->unsignedInteger('password_reset_expires_at')->nullable()->after('password_reset_token');
            }
            if (!Schema::hasColumn('users', 'password_reset_forced_by')) {
                $table->unsignedBigInteger('password_reset_forced_by')->nullable()->after('password_reset_expires_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'force_password_reset',
                'password_reset_forced_by',
                'password_reset_expires_at',
            ] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('users', 'password_reset_token')) {
                $table->dropUnique(['password_reset_token']);
                $table->dropColumn('password_reset_token');
            }
        });
    }
};
