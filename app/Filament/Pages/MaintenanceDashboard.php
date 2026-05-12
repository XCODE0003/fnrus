<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\MaintenanceAnalyticsStats;
use App\Filament\Widgets\MaintenanceArtifactsStats;
use App\Filament\Widgets\MaintenanceOrdersStats;
use App\Models\MaintenanceLog;
use App\Models\RolePermission;
use App\Services\AnalyticsResetService;
use App\Services\BulkCleanupService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MaintenanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Обслуживание';
    protected static ?string $navigationLabel = 'Обслуживание';
    protected static ?string $title = 'Обслуживание';
    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.maintenance-dashboard';
    protected static ?string $slug = 'maintenance';

    protected function getHeaderWidgets(): array
    {
        return [
            MaintenanceOrdersStats::class,
            MaintenanceArtifactsStats::class,
            MaintenanceAnalyticsStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    private function ensurePermission(string $permission): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }
        $row = RolePermission::getByPermission($user->role_id, $permission);
        return (bool) ($row->allow ?? false);
    }

    private function logAction(string $action, ?string $target, int $affected, array $details): void
    {
        MaintenanceLog::record(
            (int) (Auth::id() ?? 0),
            $action,
            $target,
            $affected,
            $details,
            request()->ip()
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->resetAnalyticsAction(),

            ActionGroup::make([
                $this->cleanupOrdersAction(),
                $this->cleanupCouponsAction(),
                $this->cleanupFilesAction(),
                $this->cleanupExportsAction(),
            ])
                ->label('Очистка')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->button(),
        ];
    }

    private function resetAnalyticsAction(): Action
    {
        return Action::make('resetAnalytics')
            ->label('Сбросить аналитику')
            ->color('warning')
            ->icon('heroicon-o-arrow-path')
            ->form([
                Forms\Components\Select::make('scope')
                    ->label('Что сбросить')
                    ->options([
                        AnalyticsResetService::SCOPE_ALL => 'Все счётчики',
                        AnalyticsResetService::SCOPE_PRODUCTS => 'Только товары',
                        AnalyticsResetService::SCOPE_CATEGORIES => 'Только категории',
                        AnalyticsResetService::SCOPE_COUPONS => 'Только промокоды',
                        AnalyticsResetService::SCOPE_SENDERS => 'Только рассылки',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('confirm')
                    ->label('Введите СБРОС для подтверждения')
                    ->required()
                    ->rule('in:СБРОС'),
            ])
            ->requiresConfirmation()
            ->action(function (array $data): void {
                if (! $this->ensurePermission('maintenance.analytics_reset')) {
                    Notification::make()->danger()->title('Нет прав')->send();
                    return;
                }
                /** @var AnalyticsResetService $analytics */
                $analytics = app(AnalyticsResetService::class);
                $result = $analytics->reset($data['scope']);
                $this->logAction('analytics.reset', $result['scope'], $result['affected'], [
                    'before' => $result['before'], 'after' => $result['after'],
                ]);
                Notification::make()->success()->title('Аналитика сброшена')->body('Затронуто: ' . $result['affected'])->send();
            });
    }

    private function cleanupOrdersAction(): Action
    {
        return Action::make('cleanupOrders')
            ->label('Заказы')
            ->color('danger')
            ->icon('heroicon-o-shopping-cart')
            ->form([
                Forms\Components\Select::make('type')
                    ->label('Какие заказы')
                    ->options([
                        BulkCleanupService::ORDER_TYPE_EXPIRED => 'Просроченные',
                        BulkCleanupService::ORDER_TYPE_PAID => 'Оплаченные (старше 365 дней)',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('limit')
                    ->label('Лимит за вызов')
                    ->numeric()->default(1000)->minValue(1)->maxValue(BulkCleanupService::MAX_PER_CALL),
                Forms\Components\TextInput::make('confirm')
                    ->label('Введите УДАЛИТЬ')
                    ->required()->rule('in:УДАЛИТЬ'),
            ])
            ->requiresConfirmation()
            ->action(function (array $data): void {
                if (! $this->ensurePermission('maintenance.cleanup_orders')) {
                    Notification::make()->danger()->title('Нет прав')->send();
                    return;
                }
                /** @var BulkCleanupService $svc */
                $svc = app(BulkCleanupService::class);
                $deleted = $svc->cleanupOrders($data['type'], (int) $data['limit']);
                $this->logAction('cleanup.orders', $data['type'], $deleted, ['requested_limit' => (int) $data['limit']]);
                Notification::make()->success()->title('Удалено заказов: ' . $deleted)->send();
            });
    }

    private function cleanupCouponsAction(): Action
    {
        return Action::make('cleanupCoupons')
            ->label('Просроченные купоны')
            ->color('danger')
            ->icon('heroicon-o-ticket')
            ->form([
                Forms\Components\TextInput::make('limit')
                    ->label('Лимит')->numeric()->default(1000)->minValue(1)->maxValue(BulkCleanupService::MAX_PER_CALL),
                Forms\Components\TextInput::make('confirm')
                    ->label('Введите УДАЛИТЬ')
                    ->required()->rule('in:УДАЛИТЬ'),
            ])
            ->requiresConfirmation()
            ->action(function (array $data): void {
                if (! $this->ensurePermission('maintenance.cleanup_coupons')) {
                    Notification::make()->danger()->title('Нет прав')->send();
                    return;
                }
                /** @var BulkCleanupService $svc */
                $svc = app(BulkCleanupService::class);
                $deleted = $svc->cleanupCoupons((int) $data['limit']);
                $this->logAction('cleanup.coupons', null, $deleted, ['requested_limit' => (int) $data['limit']]);
                Notification::make()->success()->title('Удалено купонов: ' . $deleted)->send();
            });
    }

    private function cleanupFilesAction(): Action
    {
        return Action::make('cleanupFiles')
            ->label('Файлы / вложения')
            ->color('danger')
            ->icon('heroicon-o-paper-clip')
            ->form([
                Forms\Components\Select::make('type')
                    ->label('Тип файлов')
                    ->options([
                        BulkCleanupService::FILE_TYPE_COVERS => 'Обложки',
                        BulkCleanupService::FILE_TYPE_AVATARS => 'Аватары',
                        BulkCleanupService::FILE_TYPE_FILES => 'Прочие файлы',
                    ])->required(),
                Forms\Components\TextInput::make('limit')
                    ->label('Лимит')->numeric()->default(1000)->minValue(1)->maxValue(BulkCleanupService::MAX_PER_CALL),
                Forms\Components\TextInput::make('confirm')
                    ->label('Введите УДАЛИТЬ')
                    ->required()->rule('in:УДАЛИТЬ'),
            ])
            ->requiresConfirmation()
            ->action(function (array $data): void {
                if (! $this->ensurePermission('maintenance.cleanup_files')) {
                    Notification::make()->danger()->title('Нет прав')->send();
                    return;
                }
                /** @var BulkCleanupService $svc */
                $svc = app(BulkCleanupService::class);
                $deleted = $svc->cleanupFiles($data['type'], (int) $data['limit']);
                $this->logAction('cleanup.files', $data['type'], $deleted, ['requested_limit' => (int) $data['limit']]);
                Notification::make()->success()->title('Удалено файлов: ' . $deleted)->send();
            });
    }

    private function cleanupExportsAction(): Action
    {
        return Action::make('cleanupExports')
            ->label('История экспорта')
            ->color('danger')
            ->icon('heroicon-o-arrow-down-tray')
            ->form([
                Forms\Components\TextInput::make('limit')
                    ->label('Лимит')->numeric()->default(1000)->minValue(1)->maxValue(BulkCleanupService::MAX_PER_CALL),
                Forms\Components\TextInput::make('confirm')
                    ->label('Введите УДАЛИТЬ')
                    ->required()->rule('in:УДАЛИТЬ'),
            ])
            ->requiresConfirmation()
            ->action(function (array $data): void {
                if (! $this->ensurePermission('maintenance.cleanup_exports')) {
                    Notification::make()->danger()->title('Нет прав')->send();
                    return;
                }
                /** @var BulkCleanupService $svc */
                $svc = app(BulkCleanupService::class);
                $deleted = $svc->cleanupExports((int) $data['limit']);
                $this->logAction('cleanup.exports', null, $deleted, ['requested_limit' => (int) $data['limit']]);
                Notification::make()->success()->title('Удалено записей экспорта: ' . $deleted)->send();
            });
    }
}
