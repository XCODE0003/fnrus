<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const PERMISSIONS = [
        ['permission' => 'email_broadcasts.view',   'title' => 'Email-рассылки: просмотр'],
        ['permission' => 'email_broadcasts.create', 'title' => 'Email-рассылки: создание'],
        ['permission' => 'email_broadcasts.send',   'title' => 'Email-рассылки: отправка'],
        ['permission' => 'email_broadcasts.delete', 'title' => 'Email-рассылки: удаление'],
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
