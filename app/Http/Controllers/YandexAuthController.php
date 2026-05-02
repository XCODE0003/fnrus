<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopSettings;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class YandexAuthController
{
    public function redirect()
    {
        $state = Str::random(40);
        session(['yandex_state' => $state]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => config('services.yandex.client_id'),
            'redirect_uri'  => config('services.yandex.redirect_uri'),
            'state'         => $state,
            'scope'         => 'login:email login:info',
        ]);

        return redirect('https://oauth.yandex.com/authorize?' . $params);
    }

    public function callback()
    {
        try {
            $code  = request()->query('code');
            $state = request()->query('state');

            if (!$code) {
                $error = request()->query('error_description', 'Неизвестная ошибка');
                Log::error('Yandex OAuth: no code in callback', ['error' => $error]);
                return redirect('/')->with('error', 'Ошибка авторизации через Яндекс.');
            }

            // Verify state
            $savedState = session('yandex_state');
            if (!$savedState || $state !== $savedState) {
                Log::error('Yandex OAuth: state mismatch', ['expected' => $savedState, 'got' => $state]);
                return redirect('/')->with('error', 'Ошибка авторизации через Яндекс.');
            }

            session()->forget('yandex_state');

            // Exchange code for token
            $tokenResponse = Http::asForm()->post('https://oauth.yandex.com/token', [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'client_id'     => config('services.yandex.client_id'),
                'client_secret' => config('services.yandex.client_secret'),
            ]);

            $tokenData = $tokenResponse->json();

            if (!isset($tokenData['access_token'])) {
                Log::error('Yandex OAuth: token exchange failed', ['response' => $tokenData]);
                return redirect('/')->with('error', 'Ошибка получения токена Яндекс.');
            }

            // Get user info
            $userInfoResponse = Http::withHeaders([
                'Authorization' => 'OAuth ' . $tokenData['access_token'],
            ])->get('https://login.yandex.ru/info', ['format' => 'json']);

            $yandexUser = $userInfoResponse->json();

            if (empty($yandexUser) || empty($yandexUser['id'])) {
                Log::error('Yandex OAuth: failed to get user info', ['response' => $yandexUser]);
                return redirect('/')->with('error', 'Ошибка получения данных пользователя Яндекс.');
            }

            $yandexId  = (int) $yandexUser['id'];
            $email     = $yandexUser['default_email'] ?? null;
            $firstName = $yandexUser['first_name'] ?? '';
            $login     = $yandexUser['login'] ?? '';

            $user = $this->findOrCreateUser($yandexId, $email, $firstName, $login);

            if (!$user) {
                return redirect('/')->with('error', 'Ошибка создания аккаунта.');
            }

            $sessionToken = Auth::login($user);

            return view('user.login', ['session_token' => $sessionToken]);

        } catch (QueryException $qe) {
            Log::error('Yandex OAuth SQL error: ' . $qe->getMessage());
            return redirect('/')->with('error', 'Ошибка при авторизации через Яндекс. Попробуйте позже.');
        } catch (Exception $e) {
            Log::error('Yandex OAuth error: ' . $e->getMessage());
            return redirect('/')->with('error', 'Ошибка при авторизации через Яндекс.');
        }
    }

    private function findOrCreateUser(int $yandexId, ?string $email, string $firstName, string $login): ?User
    {
        // 1. By yandex_id
        $user = User::where('yandex_id', $yandexId)->first();
        if ($user) {
            return $user;
        }

        // 2. By email — link yandex_id
        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->yandex_id = $yandexId;
                $user->save();
                return $user;
            }
        }

        // 3. Create new user
        $shop    = Shop::getDefault();
        $shopSet = ShopSettings::getDefault();

        $baseUsername = $login ?: mb_strtolower(trim($firstName));
        if (empty($baseUsername)) {
            $baseUsername = 'ya_user';
        }
        $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $baseUsername);
        if (empty($baseUsername)) {
            $baseUsername = 'ya_user';
        }

        $username = $baseUsername;
        $counter  = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        $userEmail = $email ?: 'ya_' . $yandexId . '@yandex.local';

        $user = User::create([
            'rid'                         => 0,
            'tid'                         => 0,
            'sid'                         => $shop->id,
            'mid'                         => 0,
            'email'                       => $userEmail,
            'yandex_id'                   => $yandexId,
            'username'                    => $username,
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
