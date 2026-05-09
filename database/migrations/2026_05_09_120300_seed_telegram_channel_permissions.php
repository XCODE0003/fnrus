<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds default telegram_channels.* permissions for every existing role.
 * Existing rows are left alone (admins may have already granted/denied).
 */
return new class extends Migration {
    private const PERMISSIONS = [
        ['permission' => 'telegram_channels.list',   'title' => 'Список Telegram-каналов'],
        ['permission' => 'telegram_channels.create', 'title' => 'Создание Telegram-канала'],
        ['permission' => 'telegram_channels.edit',   'title' => 'Редактирование Telegram-канала'],
        ['permission' => 'telegram_channels.delete', 'title' => 'Удаление Telegram-канала'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('role_permissions') || !Schema::hasTable('roles')) {
            return;
        }

        $roles = DB::table('roles')->pluck('id');
        if ($roles->isEmpty()) {
            return;
        }

        foreach ($roles as $roleId) {
            foreach (self::PERMISSIONS as $perm) {
                $exists = DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission', $perm['permission'])
                    ->exists();
                if ($exists) {
                    continue;
                }
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
        if (!Schema::hasTable('role_permissions')) {
            return;
        }
        DB::table('role_permissions')
            ->whereIn('permission', array_column(self::PERMISSIONS, 'permission'))
            ->delete();
    }
};
