<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a top-level "Обслуживание" entry to the admin sidebar.
 * No-op if the menu table is missing or the entry already exists.
 */
return new class extends Migration {
    private const ALIAS = 'maintenance';

    public function up(): void
    {
        if (!Schema::hasTable('menu')) return;

        $exists = DB::table('menu')->where('alias', self::ALIAS)->exists();
        if ($exists) return;

        $maxSort = (int) DB::table('menu')->where('mid', '')->max('sort');

        DB::table('menu')->insert([
            'alias' => self::ALIAS,
            'mid'   => '',
            'title' => 'Обслуживание',
            'link'  => '/admin/maintenance',
            'icon'  => 'tools',
            'sort'  => $maxSort + 1,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('menu')) return;
        DB::table('menu')->where('alias', self::ALIAS)->delete();
    }
};
