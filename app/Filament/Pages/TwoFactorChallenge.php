<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\TotpService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TwoFactorChallenge extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Двухфакторная аутентификация';
    protected static ?string $slug = 'two-factor';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.two-factor-challenge';

    public ?array $data = ['code' => '', 'recovery' => false];

    public function mount(): void
    {
        $user = Auth::user();

        // Если 2FA не настроена — челлендж нечего показывать, отправим
        // юзера обратно: при отсутствии секрета middleware пропускает его
        // на дашборд (и кидает баннер про настройку).
        if (! $user || empty($user->two_factor_secret) || empty($user->two_factor_confirmed_at)) {
            $this->redirect(self::redirectAfter(), navigate: false);
            return;
        }

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Код')
                    ->placeholder('123456 (TOTP) или резервный код')
                    ->autocomplete(false)
                    ->required()
                    ->maxLength(64)
                    ->extraInputAttributes(['inputmode' => 'text', 'autofocus' => true]),

                Forms\Components\Toggle::make('recovery')
                    ->label('Это резервный код'),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        if (! $user) {
            Notification::make()->danger()->title('Сессия истекла. Войдите снова.')->send();
            $this->redirect(self::loginUrl(), navigate: false);
            return;
        }

        $code = trim((string) ($data['code'] ?? ''));
        $useRecovery = (bool) ($data['recovery'] ?? false);

        /** @var TotpService $totp */
        $totp = app(TotpService::class);

        if ($useRecovery) {
            $hashes = (array) json_decode((string) $user->two_factor_recovery_codes, true);
            $remaining = $totp->consumeRecoveryCode($code, $hashes);
            if ($remaining === null) {
                Notification::make()->danger()->title('Неверный резервный код')->send();
                return;
            }
            DB::table('users')->where('id', $user->id)->update([
                'two_factor_recovery_codes' => json_encode($remaining),
            ]);
        } else {
            try {
                $secret = $totp->decryptSecret((string) $user->two_factor_secret);
            } catch (\Throwable $e) {
                Notification::make()->danger()->title('Не удалось прочитать секрет 2FA')->send();
                return;
            }

            if (! $totp->verify($secret, $code)) {
                Notification::make()->danger()->title('Неверный код')->send();
                return;
            }
        }

        $now = time();

        DB::table('users')->where('id', $user->id)->update(['two_factor_passed_at' => $now]);
        session()->put('2fa_passed_at', $now);

        Notification::make()->success()->title('Подтверждено')->send();

        $this->redirect(self::redirectAfter(), navigate: false);
    }

    private static function redirectAfter(): string
    {
        $next = session()->pull('2fa_redirect');
        if (is_string($next) && $next !== '') {
            return $next;
        }
        $panelPath = trim((string) config('filament.path', env('FILAMENT_PATH', 'xoalfjamapfn/admin')), '/');
        return '/' . $panelPath;
    }

    private static function loginUrl(): string
    {
        $panelPath = trim((string) config('filament.path', env('FILAMENT_PATH', 'xoalfjamapfn/admin')), '/');
        return '/' . $panelPath . '/login';
    }
}
