<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AdminUserResource\Pages;
use App\Models\MaintenanceLog;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ТЗ §4 — «Администраторы»: everyone who can reach the admin panel
 * (administrators + moderators) plus anyone who has entered it before, with
 * the ability to end their session, block and unblock panel access.
 *
 * A second resource over App\Models\User, so it declares its own slug.
 * Editing an account stays in the regular «Пользователи» tab; this one is a
 * read-only list plus the three access actions.
 *
 * Blocking here is deliberately NOT `is_ban`: is_ban locks the whole site
 * account, while admin_blocked_at only removes panel/API access and leaves
 * the storefront account working.
 */
class AdminUserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'administrators';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Пользователи';
    protected static ?string $navigationLabel = 'Администраторы';
    protected static ?string $title = 'Администраторы и модераторы';
    protected static ?string $modelLabel = 'администратор';
    protected static ?string $pluralModelLabel = 'администраторы';
    protected static ?int $navigationSort = 55;

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Anyone who reaches the panel can see the section; who they may ACT on is
     * constrained per-action (never yourself, never a higher role, never the
     * last remaining admin). Gating on main_admin_role_id would hide the tab
     * entirely on installs whose top role is min_role_id — which is the case
     * here (a single role, «Администратор» = 1).
     */
    public static function canViewAny(): bool
    {
        $user = Auth::guard('web')->user();

        return $user
            // A deploy may briefly have new PHP code before migrations finish.
            // Hide this resource during that window instead of issuing queries
            // for missing columns and taking the whole panel down with a 500.
            && Schema::hasColumns('users', [
                'admin_blocked_at',
                'admin_sessions_revoked_at',
                'last_admin_login_at',
                'admin_login_count',
            ])
            && (int) ($user->role_id ?? 0) >= (int) config('admin.min_role_id', 1);
    }

    /** Role level that may manage other admins on this install. */
    protected static function managerRoleId(): int
    {
        return min(
            (int) config('admin.main_admin_role_id', 2),
            (int) config('admin.min_role_id', 1)
        );
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    /**
     * The TZ's three audiences in one query: administrators, moderators
     * (role_id >= min_role_id) and anyone who ever entered the panel — the
     * latter matters because a demoted account still needs to be blockable.
     */
    public static function getEloquentQuery(): Builder
    {
        $minRole = (int) config('admin.min_role_id', 1);

        return parent::getEloquentQuery()->where(function (Builder $query) use ($minRole) {
            $query->where('role_id', '>=', $minRole)
                ->orWhereNotNull('last_admin_login_at');
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Пользователь')
                    ->description(fn (User $record) => $record->email)
                    ->searchable(['username', 'email'])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('role_id')
                    ->label('Роль')
                    ->formatStateUsing(fn ($state) => Role::where('id', $state)->value('title') ?? ('#' . $state))
                    ->colors([
                        'gray' => fn ($state) => (int) $state < (int) config('admin.min_role_id', 1),
                        'warning' => fn ($state) => (int) $state === (int) config('admin.min_role_id', 1),
                        'success' => fn ($state) => (int) $state >= (int) config('admin.main_admin_role_id', 2),
                    ]),

                Tables\Columns\BadgeColumn::make('admin_blocked_at')
                    ->label('Доступ')
                    ->formatStateUsing(fn ($state) => $state ? 'Заблокирован' : 'Разрешён')
                    ->colors([
                        'danger' => fn ($state) => (bool) $state,
                        'success' => fn ($state) => ! $state,
                    ])
                    ->description(fn (User $record) => $record->admin_blocked_reason),

                Tables\Columns\TextColumn::make('last_admin_login_at')
                    ->label('Последний вход в админку')
                    ->formatStateUsing(fn ($state) => $state ? date('d.m.Y H:i', (int) $state) : '—')
                    ->description(fn (User $record) => $record->last_admin_login_ip)
                    ->sortable(),

                Tables\Columns\TextColumn::make('admin_login_count')
                    ->label('Входов')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('two_factor_confirmed_at')
                    ->label('2FA')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => $record->two_factor_confirmed_at !== null),

                Tables\Columns\IconColumn::make('is_ban')->label('Бан сайта')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Роль')
                    ->options(fn () => Role::orderBy('id')->pluck('title', 'id')->all()),

                Tables\Filters\TernaryFilter::make('admin_blocked')
                    ->label('Заблокирован')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('admin_blocked_at'),
                        false: fn (Builder $q) => $q->whereNull('admin_blocked_at'),
                        blank: fn (Builder $q) => $q,
                    ),

                Tables\Filters\Filter::make('visited_panel')
                    ->label('Заходил в админку')
                    ->query(fn (Builder $q) => $q->whereNotNull('last_admin_login_at')),
            ])
            ->actions([
                self::terminateSessionAction(),
                self::blockAction(),
                self::unblockAction(),
            ])
            ->bulkActions([])
            ->defaultSort('last_admin_login_at', 'desc');
    }

    /**
     * "Завершить активную сессию". Sessions are files and the real credential
     * is a long-lived JWT cookie, so we stamp a revocation epoch instead of
     * deleting rows: FilamentSiteAuthBridge rejects any session or token that
     * predates it. Clearing two_factor_passed_at also forces a fresh code.
     */
    protected static function terminateSessionAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('terminateSession')
            ->label('Завершить сессию')
            ->icon('heroicon-o-arrow-left-on-rectangle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Завершить активную сессию')
            ->modalDescription('Пользователь будет разлогинен из админки и при следующем входе снова введёт код 2FA.')
            ->visible(fn (User $record) => $record->getKey() !== Auth::guard('web')->id())
            ->action(function (User $record): void {
                if (! self::assertActionable($record)) {
                    return;
                }

                DB::table('users')->where('id', $record->getKey())->update([
                    'admin_sessions_revoked_at' => time(),
                    'two_factor_passed_at' => null,
                ]);

                self::audit('admin.session_terminated', $record);

                Notification::make()->success()
                    ->title('Сессия завершена')
                    ->body('Доступ прерван для «' . ($record->username ?: $record->email) . '».')
                    ->send();
            });
    }

    protected static function blockAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('blockAdmin')
            ->label('Заблокировать')
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Заблокировать доступ в админку')
            ->modalDescription('Аккаунт на сайте продолжит работать — закрывается только админ-панель и админское API.')
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('Причина')
                    ->maxLength(255)
                    ->rows(2),
            ])
            ->visible(fn (User $record) => $record->admin_blocked_at === null
                && $record->getKey() !== Auth::guard('web')->id())
            ->action(function (User $record, array $data): void {
                if (! self::assertActionable($record)) {
                    return;
                }

                if (self::isLastActiveAdmin($record)) {
                    Notification::make()->danger()
                        ->title('Нельзя заблокировать последнего администратора')
                        ->body('Иначе в панель не сможет войти никто.')
                        ->send();
                    return;
                }

                DB::table('users')->where('id', $record->getKey())->update([
                    'admin_blocked_at' => time(),
                    'admin_blocked_by' => Auth::guard('web')->id(),
                    'admin_blocked_reason' => $data['reason'] ?? null,
                    // evict immediately instead of at session expiry
                    'admin_sessions_revoked_at' => time(),
                    'two_factor_passed_at' => null,
                ]);

                self::audit('admin.blocked', $record, ['reason' => $data['reason'] ?? null]);

                Notification::make()->success()
                    ->title('Доступ заблокирован')
                    ->send();
            });
    }

    protected static function unblockAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('unblockAdmin')
            ->label('Разблокировать')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Разблокировать доступ в админку')
            ->visible(fn (User $record) => $record->admin_blocked_at !== null)
            ->action(function (User $record): void {
                if (! self::assertActionable($record, allowSelf: true)) {
                    return;
                }

                // admin_sessions_revoked_at is a monotonic epoch and is left
                // alone on purpose — resetting it would revive old cookies.
                DB::table('users')->where('id', $record->getKey())->update([
                    'admin_blocked_at' => null,
                    'admin_blocked_by' => null,
                    'admin_blocked_reason' => null,
                ]);

                self::audit('admin.unblocked', $record);

                Notification::make()->success()->title('Доступ восстановлен')->send();
            });
    }

    /**
     * Re-assert the guards inside the action: Filament's visible() is UI-only
     * and a hand-crafted Livewire call still reaches action().
     */
    protected static function assertActionable(User $record, bool $allowSelf = false): bool
    {
        $actor = Auth::guard('web')->user();

        if (! $actor || (int) ($actor->role_id ?? 0) < self::managerRoleId()) {
            Notification::make()->danger()->title('Недостаточно прав')->send();
            return false;
        }

        if (! $allowSelf && $record->getKey() === $actor->getKey()) {
            Notification::make()->danger()->title('Нельзя применить действие к себе')->send();
            return false;
        }

        if ((int) ($record->role_id ?? 0) > (int) ($actor->role_id ?? 0)) {
            Notification::make()->danger()->title('Нельзя управлять аккаунтом с более высокой ролью')->send();
            return false;
        }

        return true;
    }

    /** Would blocking this account leave nobody able to enter the panel? */
    protected static function isLastActiveAdmin(User $record): bool
    {
        $mainRole = self::managerRoleId();

        if ((int) ($record->role_id ?? 0) < $mainRole) {
            return false;
        }

        $remaining = User::where('role_id', '>=', $mainRole)
            ->whereNull('admin_blocked_at')
            ->where('is_ban', '!=', 1)
            ->where('id', '!=', $record->getKey())
            ->count();

        return $remaining === 0;
    }

    protected static function audit(string $action, User $record, array $details = []): void
    {
        try {
            MaintenanceLog::record(
                Auth::guard('web')->id(),
                $action,
                'User#' . $record->getKey(),
                1,
                $details + ['username' => $record->username, 'email' => $record->email],
                request()->ip()
            );
        } catch (\Throwable $e) {
            // auditing must never break the action
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminUsers::route('/'),
        ];
    }
}
