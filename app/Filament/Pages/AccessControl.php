<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Http\Middleware\IPWhitelist;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Filament-аналог старой страницы /access:
 *   - редактирование динамического IP allow-list (поверх статического из .env)
 *   - снятие блокировки логина для пары (IP + username)
 *   - просмотр последних попыток входа
 *
 * Доступ: только role_id >= MAIN_ADMIN_ROLE_ID (по умолчанию 2).
 */
class AccessControl extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Обслуживание';
    protected static ?string $navigationLabel = 'Контроль доступа';
    protected static ?string $title = 'Контроль доступа: IP allow-list и блокировки';
    protected static ?int $navigationSort = 92;

    protected static string $view = 'filament.pages.access-control';
    protected static ?string $slug = 'access-control';

    /** @var array<string> Статический список из .env (ADMIN_ALLOWED_IPS). */
    public array $staticEnv = [];

    /** @var array<int, array{id:int, cidr:string, note:string, expires_at:?int, created_at:int}> */
    public array $dynamicIps = [];

    /** @var array<int, array{id:int, ip:string, username:string, successful:int, reason:string, created_at:string}> */
    public array $attempts = [];

    public int $now = 0;

    public static function canAccess(): bool
    {
        // Явно берём web-guard: дефолтный auth-гард в этом приложении — `api`
        // (JWT для /api/*), и Auth::user() без явного гарда вернёт null даже
        // для залогиненного через Filament юзера.
        $user = Auth::guard('web')->user();
        if ($user === null) {
            return false;
        }
        // Совпадает с гейтом самой Filament-панели (admin.min_role_id, по умолчанию 1).
        $min = (int) config('admin.min_role_id', 1);
        return (int) ($user->role_id ?? 0) >= $min;
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403, 'Доступ только администраторам.');

        $this->now = time();
        $this->refreshIps();
        $this->refreshAttempts();
    }

    public function refreshIps(): void
    {
        $this->staticEnv = array_values((array) config('admin.allowed_ips', []));
        $this->dynamicIps = DB::table('admin_allowed_ips')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'cidr' => (string) $r->cidr,
                'note' => (string) ($r->note ?? ''),
                'expires_at' => $r->expires_at !== null ? (int) $r->expires_at : null,
                'created_at' => (int) $r->created_at,
            ])
            ->all();
        $this->now = time();
    }

    public function refreshAttempts(): void
    {
        // "Контроль доступа" is about ADMIN access — show only login attempts
        // aimed at an admin account (matched by the admin's email/username),
        // not every regular site-user login. Otherwise a normal registration
        // + login would show up here with status "ок", which is confusing
        // (that account has no panel access). MySQL collation is
        // case-insensitive, so whereIn matches regardless of typed case.
        $min = (int) config('admin.min_role_id', 1);
        $adminIdentifiers = DB::table('users')
            ->where('role_id', '>=', $min)
            ->get(['email', 'username'])
            ->flatMap(fn ($u) => [(string) $u->email, (string) $u->username])
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        $query = DB::table('login_attempts')->orderBy('id', 'desc');
        if ($adminIdentifiers === []) {
            $query->whereRaw('1 = 0'); // no admins configured — show nothing
        } else {
            $query->whereIn('username', $adminIdentifiers);
        }

        $this->attempts = $query
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'ip' => (string) $r->ip,
                'username' => (string) $r->username,
                'successful' => (int) $r->successful,
                'reason' => (string) ($r->reason ?? ''),
                'created_at' => (string) $r->created_at,
            ])
            ->all();
    }

    public function deleteIp(int $id): void
    {
        if (! self::canAccess()) {
            Notification::make()->danger()->title('Нет прав')->send();
            return;
        }
        $row = DB::table('admin_allowed_ips')->where('id', $id)->first();
        if (! $row) {
            Notification::make()->danger()->title('Запись не найдена')->send();
            return;
        }
        DB::table('admin_allowed_ips')->where('id', $id)->delete();
        IPWhitelist::flushCache();
        Log::warning('admin.ip_allow.removed', [
            'cidr' => $row->cidr, 'by' => Auth::id() ?? null,
        ]);
        $this->refreshIps();
        Notification::make()->success()->title('Запись удалена')->body($row->cidr)->send();
    }

    protected function getActions(): array
    {
        return [
            Action::make('addIp')
                ->label('Добавить IP / CIDR')
                ->color('primary')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('cidr')
                        ->label('CIDR')
                        ->required()
                        ->maxLength(45)
                        ->placeholder('203.0.113.7 или 198.51.100.0/24'),
                    Forms\Components\TextInput::make('note')
                        ->label('Заметка')
                        ->maxLength(255)
                        ->placeholder('Например: офис'),
                    Forms\Components\TextInput::make('ttl_minutes')
                        ->label('TTL (минут)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('0 = бессрочно'),
                ])
                ->action(function (array $data): void {
                    if (! self::canAccess()) {
                        Notification::make()->danger()->title('Нет прав')->send();
                        return;
                    }
                    $cidr = trim((string) ($data['cidr'] ?? ''));
                    if (! $this->isValidCidr($cidr)) {
                        Notification::make()->danger()->title('Некорректный IP / CIDR')->send();
                        return;
                    }
                    $ttl = (int) ($data['ttl_minutes'] ?? 0);
                    $expiresAt = $ttl > 0 ? time() + $ttl * 60 : null;

                    try {
                        DB::table('admin_allowed_ips')->insert([
                            'cidr' => $cidr,
                            'note' => mb_substr((string) ($data['note'] ?? ''), 0, 255),
                            'created_by' => (int) (Auth::id() ?? 0),
                            'expires_at' => $expiresAt,
                            'created_at' => time(),
                        ]);
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Запись уже существует')->send();
                        return;
                    }

                    IPWhitelist::flushCache();
                    Log::warning('admin.ip_allow.added', [
                        'cidr' => $cidr, 'by' => Auth::id() ?? null, 'ttl_min' => $ttl,
                    ]);
                    $this->refreshIps();
                    Notification::make()->success()->title('IP добавлен')->body($cidr)->send();
                }),

            Action::make('unlockLogin')
                ->label('Снять блокировку логина')
                ->color('warning')
                ->icon('heroicon-o-lock-open')
                ->form([
                    Forms\Components\TextInput::make('ip')
                        ->label('IP')
                        ->required()
                        ->maxLength(45),
                    Forms\Components\TextInput::make('username')
                        ->label('Username')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    if (! self::canAccess()) {
                        Notification::make()->danger()->title('Нет прав')->send();
                        return;
                    }
                    $ip = trim((string) ($data['ip'] ?? ''));
                    $username = trim((string) ($data['username'] ?? ''));
                    if ($ip === '' || $username === '') {
                        Notification::make()->danger()->title('Укажите IP и username')->send();
                        return;
                    }
                    $key = sha1(strtolower($username) . '|' . $ip);
                    Cache::forget("login_fails:{$key}");
                    Cache::forget("login_lock:{$key}");
                    Log::warning('admin.login_unlock', [
                        'ip' => $ip, 'username' => $username, 'by' => Auth::id() ?? null,
                    ]);
                    Notification::make()->success()->title('Блокировка снята')->send();
                }),

            Action::make('refresh')
                ->label('Обновить')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    $this->refreshIps();
                    $this->refreshAttempts();
                }),
        ];
    }

    private function isValidCidr(string $cidr): bool
    {
        if ($cidr === '') {
            return false;
        }
        $parts = explode('/', $cidr, 2);
        $ip = $parts[0];
        $mask = $parts[1] ?? null;
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        if ($mask !== null) {
            if (! ctype_digit($mask)) {
                return false;
            }
            $maxBits = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 32;
            if ((int) $mask < 0 || (int) $mask > $maxBits) {
                return false;
            }
        }
        return true;
    }
}
