<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminUserResource\Pages;

use App\Filament\Resources\AdminUserResource;
use App\Models\User;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAdminUsers extends ListRecords
{
    protected static string $resource = AdminUserResource::class;

    /** ТЗ §4 — the three audiences the section has to show. */
    public function getTabs(): array
    {
        $minRole = (int) config('admin.min_role_id', 1);
        $mainRole = (int) config('admin.main_admin_role_id', 2);

        return [
            'admins' => Tab::make('Администраторы')
                ->icon('heroicon-o-shield-check')
                ->badge(fn () => User::where('role_id', '>=', $mainRole)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('role_id', '>=', $mainRole)),

            'moderators' => Tab::make('Модераторы')
                ->icon('heroicon-o-user-group')
                ->badge(fn () => User::where('role_id', '>=', $minRole)->where('role_id', '<', $mainRole)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('role_id', '>=', $minRole)->where('role_id', '<', $mainRole)),

            'visited' => Tab::make('Заходили в админку')
                ->icon('heroicon-o-clock')
                ->badge(fn () => User::whereNotNull('last_admin_login_at')->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('last_admin_login_at')),

            'blocked' => Tab::make('Заблокированные')
                ->icon('heroicon-o-lock-closed')
                ->badge(fn () => User::whereNotNull('admin_blocked_at')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('admin_blocked_at')),

            'all' => Tab::make('Все')->icon('heroicon-o-bars-3'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'admins';
    }
}
