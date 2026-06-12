<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reviews', 'product')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->string('product')->nullable()->after('link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reviews', 'product')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('product');
            });
        }
    }
};
