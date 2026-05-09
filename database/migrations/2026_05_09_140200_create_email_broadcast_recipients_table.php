<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('email_broadcast_recipients')) {
            Schema::create('email_broadcast_recipients', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('broadcast_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('email', 191)->index();
                $table->enum('status', ['queued', 'sent', 'failed', 'skipped'])
                    ->default('queued')->index();
                $table->string('error', 500)->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->unsignedInteger('sent_at')->nullable();
                $table->unsignedInteger('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_broadcast_recipients');
    }
};
