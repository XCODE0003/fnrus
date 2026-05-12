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
use Illuminate\Support\HtmlString;

class TwoFactorSetup extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Настройка двухфакторной аутентификации';
    protected static ?string $slug = 'two-factor/setup';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.two-factor-setup';

    public string $secret = '';
    public string $otpauthUri = '';
    public string $qrSvg = '';

    /** Plain recovery codes shown ONCE after successful enable. */
    public ?array $recoveryCodes = null;

    public ?array $data = ['code' => ''];

    public function mount(): void
    {
        $user = Auth::user();

        // Already configured → straight to challenge / dashboard.
        if ($user && $user->two_factor_secret && $user->two_factor_confirmed_at) {
            $panelPath = trim((string) config('filament.path', env('FILAMENT_PATH', 'admin')), '/');
            $this->redirect('/' . $panelPath, navigate: false);
            return;
        }

        /** @var TotpService $totp */
        $totp = app(TotpService::class);

        // Reuse pending secret from session if user just refreshed the page —
        // otherwise the QR (and what the user already added to their app) keeps changing.
        $sessionSecret = (string) session('2fa_pending_secret', '');
        if ($sessionSecret === '') {
            $sessionSecret = $totp->generateSecret();
            session()->put('2fa_pending_secret', $sessionSecret);
        }

        $accountLabel = $user?->email ?: ($user?->username ?: ('user#' . (int) ($user?->id ?? 0)));

        $this->secret = $sessionSecret;
        $this->otpauthUri = $totp->provisioningUri($accountLabel, $sessionSecret);
        $this->qrSvg = $this->renderQr($this->otpauthUri);

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Код подтверждения')
                    ->placeholder('Шестизначный код из приложения')
                    ->autocomplete(false)
                    ->required()
                    ->maxLength(8)
                    ->extraInputAttributes(['inputmode' => 'numeric', 'autofocus' => true]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        if (! $user) {
            Notification::make()->danger()->title('Сессия истекла. Войдите снова.')->send();
            $panelPath = trim((string) config('filament.path', env('FILAMENT_PATH', 'admin')), '/');
            $this->redirect('/' . $panelPath . '/login', navigate: false);
            return;
        }

        $secret = (string) session('2fa_pending_secret', '');
        if ($secret === '') {
            Notification::make()->danger()->title('Сессия настройки 2FA не найдена. Обновите страницу.')->send();
            return;
        }

        /** @var TotpService $totp */
        $totp = app(TotpService::class);

        $code = trim((string) ($data['code'] ?? ''));
        if (! $totp->verify($secret, $code)) {
            Notification::make()->danger()->title('Неверный код')->send();
            return;
        }

        $recoveryPlain = $totp->generateRecoveryCodes();
        $recoveryHashed = $totp->hashRecoveryCodes($recoveryPlain);

        $now = time();
        DB::table('users')->where('id', $user->id)->update([
            'two_factor_secret' => $totp->encryptSecret($secret),
            'two_factor_recovery_codes' => json_encode($recoveryHashed),
            'two_factor_confirmed_at' => $now,
            'two_factor_passed_at' => $now,
        ]);

        session()->forget('2fa_pending_secret');
        session()->put('2fa_passed_at', $now);

        $this->recoveryCodes = $recoveryPlain;

        Notification::make()->success()->title('Двухфакторная защита включена')->send();
    }

    public function done(): void
    {
        $panelPath = trim((string) config('filament.path', env('FILAMENT_PATH', 'admin')), '/');
        $this->redirect('/' . $panelPath, navigate: false);
    }

    public function recoveryCodesText(): string
    {
        return implode("\n", $this->recoveryCodes ?? []);
    }

    private function renderQr(string $uri): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(240, 1),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        return $writer->writeString($uri);
    }
}
