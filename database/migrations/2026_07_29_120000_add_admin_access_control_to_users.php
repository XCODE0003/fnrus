<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ТЗ §4 — the «Администраторы» tab needs to list everyone who can (or once
 * could) reach the admin panel, and to block them / end their session.
 *
 * Two things the schema could not express before:
 *  - blocking the PANEL specifically. `is_ban` bans the whole site account;
 *    an admin who should merely lose panel access must keep their storefront
 *    account, so it gets its own column.
 *  - ending a session. Sessions are files (SESSION_DRIVER=file) and the real
 *    credential is a 30-day JWT cookie that cannot be revoked without holding
 *    the token, so we store a revocation epoch and reject anything issued
 *    before it.
 *
 * All timestamps are unix ints, matching this table's existing convention.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'admin_blocked_at')) {
                $table->unsignedInteger('admin_blocked_at')->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'admin_blocked_by')) {
                $table->unsignedBigInteger('admin_blocked_by')->nullable();
            }
            if (!Schema::hasColumn('users', 'admin_blocked_reason')) {
                $table->string('admin_blocked_reason', 255)->nullable();
            }
            if (!Schema::hasColumn('users', 'admin_sessions_revoked_at')) {
                $table->unsignedInteger('admin_sessions_revoked_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_admin_login_at')) {
                $table->unsignedInteger('last_admin_login_at')->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'last_admin_login_ip')) {
                $table->string('last_admin_login_ip', 45)->nullable();
            }
            if (!Schema::hasColumn('users', 'admin_login_count')) {
                $table->unsignedInteger('admin_login_count')->default(0);
            }
        });

        $this->backfillLastAdminLogin();
    }

    /**
     * Seed last_admin_login_at from the login history so the tab is not empty
     * on day one. login_attempts stores the typed username, not a user id, so
     * this is a best-effort match on email/username — accurate tracking starts
     * from deploy.
     */
    private function backfillLastAdminLogin(): void
    {
        if (!Schema::hasTable('login_attempts') || !Schema::hasColumn('users', 'last_admin_login_at')) {
            return;
        }

        $minRole = (int) config('admin.min_role_id', 1);

        DB::table('users')
            ->where('role_id', '>=', $minRole)
            ->whereNull('last_admin_login_at')
            ->select('id', 'email', 'username')
            ->orderBy('id')
            ->chunk(200, function ($users) {
                foreach ($users as $user) {
                    $names = array_values(array_filter([$user->email, $user->username]));
                    if (!$names) continue;

                    $last = DB::table('login_attempts')
                        ->where('successful', 1)
                        ->whereIn('username', $names)
                        ->max('created_at');

                    if (!$last) continue;

                    DB::table('users')->where('id', $user->id)->update([
                        'last_admin_login_at' => is_numeric($last) ? (int) $last : strtotime((string) $last),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'admin_blocked_at',
                'admin_blocked_by',
                'admin_blocked_reason',
                'admin_sessions_revoked_at',
                'last_admin_login_at',
                'last_admin_login_ip',
                'admin_login_count',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
