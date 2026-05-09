<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('maintenance_logs')) {
            Schema::create('maintenance_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('admin_id')->index();
                $table->string('action', 64)->index()->comment('analytics.reset, cleanup.orders, ...');
                $table->string('target', 64)->nullable()->comment('Sub-target, e.g. "expired" / "paid" / "covers"');
                $table->unsignedInteger('affected')->default(0)->comment('Rows touched');
                $table->json('details')->nullable()->comment('Before/after snapshot, filters, etc.');
                $table->string('ip', 64)->nullable();
                $table->unsignedInteger('created_at')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
