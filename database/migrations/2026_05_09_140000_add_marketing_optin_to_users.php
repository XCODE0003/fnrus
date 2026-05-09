<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds opt-in flag for marketing/broadcast emails plus an opaque
 * unsubscribe token so the recipient can opt out via a one-click
 * link without authenticating.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_notify_marketing')) {
                $table->unsignedTinyInteger('email_notify_marketing')->default(1)->after('email_notify_status_changed');
            }
            if (!Schema::hasColumn('users', 'unsubscribe_token')) {
                $table->string('unsubscribe_token', 64)->nullable()->unique()->after('email_notify_marketing');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) return;

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_notify_marketing')) {
                $table->dropColumn('email_notify_marketing');
            }
            if (Schema::hasColumn('users', 'unsubscribe_token')) {
                $table->dropUnique(['unsubscribe_token']);
                $table->dropColumn('unsubscribe_token');
            }
        });
    }
};
