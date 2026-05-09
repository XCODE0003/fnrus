<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds maintenance.* permissions. Default allow=0 — main admin must
 * grant them explicitly through the role-management UI. This is on
 * purpose: every action is destructive.
 */
return new class extends Migration {
    private const PERMISSIONS = [
        ['permission' => 'maintenance.view',             'title' => 'Раздел обслуживания: просмотр'],
        ['permission' => 'maintenance.analytics_reset',  'title' => 'Сброс аналитики'],
        ['permission' => 'maintenance.cleanup_orders',   'title' => 'Очистка заказов'],
        ['permission' => 'maintenance.cleanup_coupons',  'title' => 'Очистка промокодов'],
        ['permission' => 'maintenance.cleanup_files',    'title' => 'Очистка файлов'],
        ['permission' => 'maintenance.cleanup_exports',  'title' => 'Очистка истории экспорта'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('role_permissions') || !Schema::hasTable('roles')) {
            return;
        }

        $roleIds = DB::table('roles')->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach (self::PERMISSIONS as $perm) {
                $exists = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission', $perm['permission'])
                    ->exists();
                if ($exists) continue;
                DB::table('role_permissions')->insert([
                    'role_id'    => $roleId,
                    'title'      => $perm['title'],
                    'permission' => $perm['permission'],
                    'allow'      => 0,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('role_permissions')) return;
        DB::table('role_permissions')
            ->whereIn('permission', array_column(self::PERMISSIONS, 'permission'))
            ->delete();
    }
};
