<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('statuses')) {
            Schema::table('statuses', function (Blueprint $table) {
                if (!Schema::hasColumn('statuses', 'message_template')) {
                    $table->text('message_template')->nullable()->after('status');
                }
                if (!Schema::hasColumn('statuses', 'image_path')) {
                    $table->string('image_path', 500)->nullable()->after('message_template');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('statuses')) {
            Schema::table('statuses', function (Blueprint $table) {
                if (Schema::hasColumn('statuses', 'message_template')) {
                    $table->dropColumn('message_template');
                }
                if (Schema::hasColumn('statuses', 'image_path')) {
                    $table->dropColumn('image_path');
                }
            });
        }
    }
};
