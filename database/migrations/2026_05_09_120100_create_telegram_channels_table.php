<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('telegram_channels')) {
            Schema::create('telegram_channels', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sid')->index()->comment('Shop id');
                $table->string('title', 255);
                $table->string('chat_id', 64)->comment('Telegram chat_id, e.g. -100xxxxxxxxxx for channels, @username, or numeric for groups');
                $table->enum('type', ['channel', 'group'])->default('channel');
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedInteger('created_at')->nullable();
                $table->unsignedInteger('updated_at')->nullable();
                $table->unique(['sid', 'chat_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_channels');
    }
};
