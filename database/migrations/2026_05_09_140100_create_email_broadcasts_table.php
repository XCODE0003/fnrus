<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('email_broadcasts')) {
            Schema::create('email_broadcasts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('admin_id')->index();
                $table->string('subject', 255);
                $table->longText('body_html');
                $table->longText('body_text')->nullable();
                $table->enum('status', ['draft', 'queued', 'sending', 'sent', 'failed', 'cancelled'])
                    ->default('draft')->index();
                $table->json('filters')->nullable()->comment('Recipient filter snapshot');
                $table->unsignedInteger('recipients_total')->default(0);
                $table->unsignedInteger('recipients_sent')->default(0);
                $table->unsignedInteger('recipients_failed')->default(0);
                $table->unsignedInteger('scheduled_at')->nullable();
                $table->unsignedInteger('started_at')->nullable();
                $table->unsignedInteger('finished_at')->nullable();
                $table->unsignedInteger('created_at');
                $table->unsignedInteger('updated_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_broadcasts');
    }
};
