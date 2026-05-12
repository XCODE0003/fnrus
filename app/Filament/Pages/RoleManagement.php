<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Member;
use App\Models\Role;
use App\Models\RolePermission;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Аналог старой /admin/access + RoleController CRUD ролей.
 *
 * Стейт целиком живёт в $data — Filament-форма привязана к нему через
 * statePath('data'). Это решает проблему «чекбоксы не отмечаются»:
 * раньше форма пыталась писать в публичные свойства типа $perms_orders,
 * которых на классе нет, и значения терялись между Livewire-запросами.
 */
class RoleManagement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Управление ролями';
    protected static ?string $title = 'Управление ролями и разрешениями';
    protected static ?int $navigationSort = 65;

    protected static string $view = 'filament.pages.role-management';
    protected static ?string $slug = 'role-management';

    /** Те же группы, что и в RolePermissionController::list_by_id. */
    public const SECTIONS = [
        'analytics'       => 'Аналитика',
        'orders'          => 'Заказы',
        'tickets'         => 'Тикеты',
        'withdrawals'     => 'Выводы',
        'statuses'        => 'Статусы',
        'products'        => 'Товары',
        'categories'      => 'Категории',
        'materials'       => 'Материалы',
        'coupons'         => 'Промокоды',
        'history_exports' => 'История экспорта',
        'members'         => 'Пользователи',
        'senders'         => 'Рассылки',
        'instructions'    => 'Инструкции',
        'faq'             => 'Вопрос-ответ',
        'ads_links'       => 'Рекламные ссылки',
        'files'           => 'Файлы',
        'settings'        => 'Настройки',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $first = Role::orderBy('sort')->first();
        $this->form->fill($this->buildState($first?->id));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('roleId')
                    ->label('Роль')
                    ->options(fn () => Role::orderBy('sort')->pluck('title', 'id')->all())
                    ->live()
                    ->afterStateUpdated(function ($state): void {
                        $this->form->fill($this->buildState($state === null || $state === '' ? null : (int) $state));
                    })
                    ->selectablePlaceholder(false)
                    ->columnSpanFull(),

                Forms\Components\Grid::make()->columns(2)->schema(
                    $this->buildPermissionSections()
                ),
            ])
            ->statePath('data');
    }

    /** @return array<int, Forms\Components\Section> */
    protected function buildPermissionSections(): array
    {
        $sections = [];
        foreach (self::SECTIONS as $alias => $title) {
            $sections[] = Forms\Components\Section::make($title)
                ->schema([
                    Forms\Components\CheckboxList::make("perms_{$alias}")
                        ->label('')
                        ->options(fn () => $this->permissionOptions($alias))
                        ->columns(1)
                        ->bulkToggleable(),
                ])
                ->collapsible()
                ->collapsed(true);
        }
        return $sections;
    }

    /** @return array<string, string> */
    protected function permissionOptions(string $alias): array
    {
        $roleId = $this->currentRoleId();
        if ($roleId === null) {
            return [];
        }
        $rows = RolePermission::where('role_id', $roleId)
            ->where('permission', 'like', $alias . '.%')
            ->orderBy('id')
            ->get();

        $opts = [];
        foreach ($rows as $r) {
            $opts[$r->permission] = $r->title;
        }
        return $opts;
    }

    /** @return array<string, mixed> */
    protected function buildState(?int $roleId): array
    {
        $state = ['roleId' => $roleId];
        if ($roleId === null) {
            return $state;
        }
        foreach (array_keys(self::SECTIONS) as $alias) {
            $allowed = RolePermission::where('role_id', $roleId)
                ->where('permission', 'like', $alias . '.%')
                ->where('allow', 1)
                ->pluck('permission')
                ->all();
            $state["perms_{$alias}"] = $allowed;
        }
        return $state;
    }

    protected function currentRoleId(): ?int
    {
        $id = $this->data['roleId'] ?? null;
        return $id === null || $id === '' ? null : (int) $id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Сохранить разрешения')
                ->color('primary')
                ->icon('heroicon-o-check')
                ->action(fn () => $this->savePermissions()),

            Action::make('rename')
                ->label('Переименовать роль')
                ->color('gray')
                ->icon('heroicon-o-pencil')
                ->visible(fn () => $this->currentRoleId() !== null)
                ->fillForm(fn () => ['title' => Role::where('id', $this->currentRoleId())->value('title') ?? ''])
                ->form([
                    Forms\Components\TextInput::make('title')
                        ->label('Название')
                        ->required()
                        ->minLength(2)
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $roleId = $this->currentRoleId();
                    if ($roleId === null) {
                        return;
                    }
                    Role::where('id', $roleId)->update(['title' => $data['title']]);
                    Notification::make()->success()->title('Сохранено')->send();
                }),

            Action::make('addRole')
                ->label('Новая роль')
                ->color('success')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('title')
                        ->label('Название')
                        ->required()
                        ->minLength(2)
                        ->maxLength(255)
                        ->placeholder('Введите название роли'),
                ])
                ->action(function (array $data): void {
                    $endSort = (int) (Role::orderByDesc('sort')->value('sort') ?? 0) + 1;
                    $newRoleId = Role::insertGetId(['title' => $data['title'], 'sort' => $endSort]);

                    // Копируем все разрешения у role_id=1 — как RoleController::create.
                    $template = RolePermission::where('role_id', 1)->get();
                    foreach ($template as $r) {
                        RolePermission::insert([
                            'role_id' => $newRoleId,
                            'title' => $r->title,
                            'permission' => $r->permission,
                            'allow' => 1,
                        ]);
                    }

                    $this->form->fill($this->buildState($newRoleId));
                    Notification::make()->success()->title('Роль создана')->body($data['title'])->send();
                }),

            Action::make('deleteRole')
                ->label('Удалить роль')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->visible(fn () => $this->currentRoleId() !== null && $this->currentRoleId() > 1)
                ->requiresConfirmation()
                ->modalHeading('Удалить роль?')
                ->modalDescription('Пользователи с этой ролью получат role_id=0. Действие необратимо.')
                ->action(function (): void {
                    $id = $this->currentRoleId();
                    if ($id === null || $id <= 1) {
                        return;
                    }
                    Member::where('role_id', $id)->update(['role_id' => 0]);
                    RolePermission::where('role_id', $id)->delete();
                    Role::where('id', $id)->delete();

                    $nextId = (int) (Role::orderBy('sort')->value('id') ?? 0) ?: null;
                    $this->form->fill($this->buildState($nextId));
                    Notification::make()->success()->title('Роль удалена')->send();
                }),
        ];
    }

    protected function savePermissions(): void
    {
        $roleId = $this->currentRoleId();
        if ($roleId === null) {
            Notification::make()->danger()->title('Роль не выбрана')->send();
            return;
        }
        $state = $this->form->getState();

        foreach (array_keys(self::SECTIONS) as $alias) {
            $allowed = (array) ($state["perms_{$alias}"] ?? []);
            $allowedSet = array_flip($allowed);

            $rows = RolePermission::where('role_id', $roleId)
                ->where('permission', 'like', $alias . '.%')
                ->get();

            foreach ($rows as $r) {
                $shouldAllow = isset($allowedSet[$r->permission]) ? 1 : 0;
                if ((int) $r->allow !== $shouldAllow) {
                    $r->allow = $shouldAllow;
                    $r->save();
                }
            }
        }

        Notification::make()->success()->title('Разрешения сохранены')->send();
    }
}
