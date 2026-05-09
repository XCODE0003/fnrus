<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const PERMISSIONS = [
        ['permission' => 'members.force_password_reset', 'title' => 'Принудительный сброс пароля пользователю'],
        ['permission' => 'admins.force_password_reset',  'title' => 'Принудительный сброс пароля администратору'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('role_permissions') || !Schema::hasTable('roles')) return;

        foreach (DB::table('roles')->pluck('id') as $roleId) {
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
