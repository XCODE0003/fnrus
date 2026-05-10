<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const ALIAS = 'email_broadcasts';

    public function up(): void
    {
        if (!Schema::hasTable('menu')) return;
        if (DB::table('menu')->where('alias', self::ALIAS)->exists()) return;

        $maxSort = (int) DB::table('menu')->where('mid', '')->max('sort');

        $prefix = trim((string) (env('ADMIN_URL_PREFIX') ?: 'admin'), '/');

        DB::table('menu')->insert([
            'alias' => self::ALIAS,
            'mid'   => '',
            'title' => 'Email-рассылки',
            'link'  => '/' . $prefix . '/email-broadcasts',
            'icon'  => 'envelope-open-text',
            'sort'  => $maxSort + 1,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('menu')) return;
        DB::table('menu')->where('alias', self::ALIAS)->delete();
    }
};
