<?php

namespace App\Http\Controllers;

use App\Services\LoginAudit;
use App\Models\Shop;
use App\Models\ShopSettings;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Telegram\Bot\Api;

class TelegramAuthController
{
    /**
     * Шаг 1 диплинк-входа: создаём одноразовый токен входа и отдаём фронту
     * ссылку на бота вида https://t.me/<bot>?start=login_<token>.
     */
    public function loginStart()
    {
        $bot = $this->resolveBotUsername();
        if (empty($bot)) {
            return response()->json(['ok' => false, 'description' => 'Вход через Telegram временно недоступен.'], 200);
        }
        $token = Str::random(40);
        Cache::put('tglogin:' . $token, 'pending', 300); // 5 минут
        $link = 'https://t.me/' . $bot . '?start=login_' . $token;
        return response()->json(['ok' => true, 'token' => $token, 'link' => $link]);
    }

    /**
     * Username бота для диплинка. Берём из TELEGRAM_BOT_USERNAME; если он не
     * задан (частая причина «Вход через Telegram временно недоступен»), но есть
     * TELEGRAM_BOT_TOKEN — добываем username из самого бота через getMe и
     * кешируем. Пустой результат НЕ кешируем, чтобы разовый сбой API не
     * «залипал» на неделю.
     */
    private function resolveBotUsername(): string
    {
        $configured = trim((string) config('services.telegram.bot_username'));
        if ($configured !== '') {
            return ltrim($configured, '@');
        }

        $cached = Cache::get('tg_bot_username');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            return '';
        }
        try {
            $username = ltrim((string) (new Api($token))->getMe()->getUsername(), '@');
            if ($username !== '') {
                Cache::put('tg_bot_username', $username, 604800); // 7 дней
                return $username;
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram getMe (bot username) failed: ' . $e->getMessage());
        }
        return '';
    }

    /**
     * Шаг 3 диплинк-входа: фронт опрашивает этот эндпоинт. Когда бот подтвердил
     * вход (положил user_id в кэш), выдаём JWT — фронт ставит session_token и логинится.
     */
    public function loginPoll($token)
    {
        $val = Cache::get('tglogin:' . (string) $token);
        if (empty($val) || $val === 'pending') {
            return response()->json(['ok' => false, 'status' => 'pending']);
        }
        $user = User::find((int) $val);
        if (!$user || (int) ($user->is_ban ?? 0) === 1) {
            Cache::forget('tglogin:' . (string) $token);
            return response()->json(['ok' => false, 'status' => 'error']);
        }
        Cache::forget('tglogin:' . (string) $token);
        $sessionToken = Auth::login($user);
        // «Контроль доступа» reads login_attempts; without this a Telegram
        // sign-in was never recorded there.
        LoginAudit::success($user, 'telegram');
        return response()->json(['ok' => true, 'session_token' => $sessionToken]);
    }

    // Принимает данные от Telegram Login Widget (data-auth-url), проверяет
    // подпись и авторизует/создаёт пользователя по Telegram ID.
    public function callback()
    {
        try {
            $botToken = config('services.telegram.bot_token');
            if (!$botToken) {
                Log::error('Telegram OAuth: bot token not configured');
                return redirect('/')->with('error', 'Вход через Telegram временно недоступен.');
            }

            $authData = request()->only([
                'id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date', 'hash',
            ]);

            if (empty($authData['id']) || empty($authData['hash'])) {
                return redirect('/')->with('error', 'Ошибка авторизации через Telegram.');
            }

            $checkHash = (string) $authData['hash'];
            unset($authData['hash']);

            // Строка проверки: отсортированные key=value, склеенные через \n.
            $pairs = [];
            foreach ($authData as $key => $value) {
                if ($value !== null && $value !== '') {
                    $pairs[] = $key . '=' . $value;
                }
            }
            sort($pairs);
            $dataCheckString = implode("\n", $pairs);

            $secretKey = hash('sha256', $botToken, true);
            $hmac = hash_hmac('sha256', $dataCheckString, $secretKey);

            if (!hash_equals($hmac, $checkHash)) {
                Log::error('Telegram OAuth: hash mismatch');
                return redirect('/')->with('error', 'Не удалось подтвердить вход через Telegram.');
            }

            // Данные не старше суток.
            if (isset($authData['auth_date']) && (time() - (int) $authData['auth_date']) > 86400) {
                return redirect('/')->with('error', 'Срок действия данных Telegram истёк. Повторите вход.');
            }

            $telegramId = (int) $authData['id'];
            $username   = $authData['username'] ?? '';
            $firstName  = $authData['first_name'] ?? '';

            $user = $this->findOrCreateUser($telegramId, $username, $firstName);

            if (!$user) {
                return redirect('/')->with('error', 'Ошибка создания аккаунта.');
            }

            $sessionToken = Auth::login($user);
            LoginAudit::success($user, 'telegram_widget');

            return view('user.login', ['session_token' => $sessionToken]);

        } catch (QueryException $qe) {
            Log::error('Telegram OAuth SQL error: ' . $qe->getMessage());
            return redirect('/')->with('error', 'Ошибка при авторизации через Telegram. Попробуйте позже.');
        } catch (Exception $e) {
            Log::error('Telegram OAuth error: ' . $e->getMessage());
            return redirect('/')->with('error', 'Ошибка при авторизации через Telegram.');
        }
    }

    private function findOrCreateUser(int $telegramId, string $username, string $firstName): ?User
    {
        // 1. По Telegram ID.
        $user = User::where('tid', $telegramId)->first();
        if ($user) {
            return $user;
        }

        // 2. Новый пользователь (Telegram не отдаёт email).
        $shop    = Shop::getDefault();
        $shopSet = ShopSettings::getDefault();

        $baseUsername = $username ?: mb_strtolower(trim($firstName));
        $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $baseUsername);
        if (empty($baseUsername)) {
            $baseUsername = 'tg_user';
        }

        $finalUsername = $baseUsername;
        $counter = 1;
        while (User::where('username', $finalUsername)->exists()) {
            $finalUsername = $baseUsername . $counter;
            $counter++;
        }

        $user = User::create([
            'rid'                         => 0,
            'tid'                         => $telegramId,
            'sid'                         => $shop->id,
            'mid'                         => 0,
            'email'                       => 'tg_' . $telegramId . '@telegram.local',
            'yandex_id'                   => 0,
            'username'                    => $finalUsername,
            'password'                    => Hash::make(Str::random(32)),
            'tz'                          => 'Europe/Moscow',
            'locale'                      => 'RU',
            'currency'                    => 'RUB',
            'remember_code'               => Str::random(32),
            'remember_token'              => Str::random(32),
            'balance_main'                => 0,
            'balance_affiliate'           => 0,
            'ref_percent'                 => $shopSet->ref_percent,
            'ref_code'                    => Str::random(10),
            'role_id'                     => 0,
            'is_ban'                      => 0,
            'is_active'                   => 0,
            'is_agreement'                => 1,
            'email_notify_tickets'        => 1,
            'email_notify_orders'         => 1,
            'email_notify_status_changed' => 1,
            'tstep'                       => 0,
            'tdata'                       => '{}',
            'created_at'                  => strtotime('NOW'),
        ]);

        return $user;
    }
}
