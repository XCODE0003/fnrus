<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ТЗ §4 — split the single "Пользовательское соглашение" page into two:
 * Политика конфиденциальности (/privacy) and Условия пользования (/terms).
 *
 * Adds admin-editable content columns for both, and seeds the terms text from
 * the old combined policy (the old page was mostly terms). policy_content_* is
 * intentionally kept for now — the legacy admin API still reads it.
 */
return new class extends Migration
{
    private array $columns = [
        'privacy_content_ru',
        'privacy_content_en',
        'terms_content_ru',
        'terms_content_en',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('shops_settings')) {
            return;
        }

        Schema::table('shops_settings', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (!Schema::hasColumn('shops_settings', $column)) {
                    $table->mediumText($column)->nullable();
                }
            }
        });

        // Carry the existing agreement over to the terms page so nothing is lost.
        foreach (['ru', 'en'] as $locale) {
            if (Schema::hasColumn('shops_settings', 'policy_content_' . $locale)) {
                DB::table('shops_settings')
                    ->whereNull('terms_content_' . $locale)
                    ->whereNotNull('policy_content_' . $locale)
                    ->where('policy_content_' . $locale, '<>', '')
                    ->update([
                        'terms_content_' . $locale => DB::raw('policy_content_' . $locale),
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('shops_settings')) {
            return;
        }

        Schema::table('shops_settings', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('shops_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
