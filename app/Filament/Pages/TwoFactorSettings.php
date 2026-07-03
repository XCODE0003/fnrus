<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\TotpService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Управление СВОЕЙ двухфакторной аутентификацией (TOTP): статус, подключение/
 * переподключение (QR), перевыпуск резервных кодов, отключение. Действия,
 * меняющие состояние у уже включённой 2FA, требуют текущий код (TOTP или
 * резервный) — чтобы перехваченная сессия не могла её снять.
 */
class TwoFactorSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Настройки';
    protected static ?string $navigationLabel = 'Двухфакторная аутентификация';
    protected static ?string $title = 'Двухфакторная аутентификация (2FA)';
    protected static ?int $navigationSort = 91;

    protected static string $view = 'filament.pages.two-factor-settings';
    protected static ?string $slug = 'settings/two-factor';

    /** 2FA уже подключена у текущего пользователя. */
    public bool $enabled = false;

    /** Обязательна ли 2FA политикой (тогда «отключить» недоступно). */
    public bool $enforced = true;

    /** Идёт подключение/переподключение (показываем QR + ввод кода). */
    public bool $configuring = false;

    public string $secret = '';
    public string $qrSvg = '';

    /** Резервные коды показываем ОДИН раз после включения/перевыпуска. */
    public ?array $recoveryCodes = null;

    public ?array $data = ['code' => ''];

    public function mount(): void
    {
        $this->enforced = (bool) config('admin.two_factor.enforced', true);
        $this->refreshStatus();
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Код из приложения')
                    ->placeholder('6 цифр (или резервный код)')
                    ->autocomplete(false)
                    ->maxLength(20)
                    ->extraInputAttributes(['inputmode' => 'numeric']),
            ])
            ->statePath('data');
    }

    private function refreshStatus(): void
    {
        $user = Auth::user();
        $this->enabled = (bool) ($user && $user->two_factor_secret && $user->two_factor_confirmed_at);

        // Если не подключена — сразу готовим QR для подключения.
        $this->configuring = ! $this->enabled;
        if ($this->configuring) {
            $this->prepareSecret();
        }
    }

    /** Генерируем (или переиспользуем из сессии) ожидающий секрет + QR. */
    private function prepareSecret(bool $fresh = false): void
    {
        /** @var TotpService $totp */
        $totp = app(TotpService::class);
        $user = Auth::user();

        $sessionSecret = (string) session('2fa_pending_secret', '');
        if ($fresh || $sessionSecret === '') {
            $sessionSecret = $totp->generateSecret();
            session()->put('2fa_pending_secret', $sessionSecret);
        }

        $accountLabel = $user?->email ?: ($user?->username ?: ('user#' . (int) ($user?->id ?? 0)));
        $this->secret = $sessionSecret;
        $this->qrSvg = $this->renderQr($totp->provisioningUri($accountLabel, $sessionSecret));
    }

    /** Кнопка «Переподключить» у уже включённой 2FA. */
    public function startReconfigure(): void
    {
        $this->configuring = true;
        $this->recoveryCodes = null;
        $this->data['code'] = '';
        $this->prepareSecret(fresh: true);
    }

    public function cancelReconfigure(): void
    {
        session()->forget('2fa_pending_secret');
        $this->data['code'] = '';
        $this->refreshStatus();
    }

    /** Подтвердить подключение/переподключение введённым кодом. */
    public function enable(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->redirectToLogin();
            return;
        }

        $secret = (string) session('2fa_pending_secret', '');
        if ($secret === '') {
            Notification::make()->danger()->title('Сессия настройки истекла. Обновите страницу.')->send();
            return;
        }

        /** @var TotpService $totp */
        $totp = app(TotpService::class);
        $code = trim((string) ($this->form->getState()['code'] ?? ''));

        if (! $totp->verify($secret, $code)) {
            Notification::make()->danger()->title('Неверный код')->send();
            return;
        }

        $recoveryPlain = $totp->generateRecoveryCodes();
        $now = time();
        DB::table('users')->where('id', $user->id)->update([
            'two_factor_secret' => $totp->encryptSecret($secret),
            'two_factor_recovery_codes' => json_encode($totp->hashRecoveryCodes($recoveryPlain)),
            'two_factor_confirmed_at' => $now,
            'two_factor_passed_at' => $now,
        ]);

        session()->forget('2fa_pending_secret');
        session()->put('2fa_passed_at', $now);

        $this->recoveryCodes = $recoveryPlain;
        $this->enabled = true;
        $this->configuring = false;
        $this->data['code'] = '';

        Notification::make()->success()->title('Двухфакторная защита включена')->send();
    }

    /** Перевыпустить резервные коды (нужен текущий код). */
    public function regenerateRecoveryCodes(): void
    {
        $user = Auth::user();
        if (! $user || ! $this->enabled) {
            return;
        }
        $code = trim((string) ($this->form->getState()['code'] ?? ''));
        if (! $this->verifyCurrent($code)) {
            Notification::make()->danger()->title('Неверный код — коды не перевыпущены')->send();
            return;
        }

        /** @var TotpService $totp */
        $totp = app(TotpService::class);
        $recoveryPlain = $totp->generateRecoveryCodes();
        DB::table('users')->where('id', $user->id)->update([
            'two_factor_recovery_codes' => json_encode($totp->hashRecoveryCodes($recoveryPlain)),
        ]);

        $this->recoveryCodes = $recoveryPlain;
        $this->data['code'] = '';
        Notification::make()->success()->title('Резервные коды перевыпущены')->send();
    }

    /** Отключить 2FA (нужен текущий код). Недоступно, если 2FA обязательна. */
    public function disable(): void
    {
        $user = Auth::user();
        if (! $user || ! $this->enabled) {
            return;
        }
        if ($this->enforced) {
            Notification::make()->warning()->title('2FA обязательна политикой и не может быть отключена')->send();
            return;
        }
        $code = trim((string) ($this->form->getState()['code'] ?? ''));
        if (! $this->verifyCurrent($code)) {
            Notification::make()->danger()->title('Неверный код — 2FA не отключена')->send();
            return;
        }

        DB::table('users')->where('id', $user->id)->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_passed_at' => null,
        ]);
        session()->forget('2fa_passed_at');

        $this->recoveryCodes = null;
        $this->data['code'] = '';
        $this->refreshStatus();
        Notification::make()->success()->title('Двухфакторная защита отключена')->send();
    }

    /** Проверка текущего кода: сперва TOTP по активному секрету, затем резервный. */
    private function verifyCurrent(string $code): bool
    {
        if ($code === '') {
            return false;
        }
        $user = Auth::user();
        /** @var TotpService $totp */
        $totp = app(TotpService::class);

        try {
            $secret = $totp->decryptSecret((string) $user->two_factor_secret);
            if ($secret !== '' && $totp->verify($secret, $code)) {
                return true;
            }
        } catch (\Throwable $e) {
            // fall through to recovery codes
        }

        $hashes = json_decode((string) $user->two_factor_recovery_codes, true);
        return is_array($hashes) && $totp->consumeRecoveryCode($code, $hashes) !== null;
    }

    public function recoveryCodesText(): string
    {
        return implode("\n", $this->recoveryCodes ?? []);
    }

    private function redirectToLogin(): void
    {
        $panelPath = trim((string) config('filament.path', env('FILAMENT_PATH', 'xoalfjamapfn/admin')), '/');
        Notification::make()->danger()->title('Сессия истекла. Войдите снова.')->send();
        $this->redirect('/' . $panelPath . '/login', navigate: false);
    }

    private function renderQr(string $uri): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(240, 1),
            new SvgImageBackEnd()
        );
        return (new Writer($renderer))->writeString($uri);
    }
}
