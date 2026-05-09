<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Member;
use App\Models\Shop;
use App\Models\ShopSettings;
use Exception;
use Illuminate\Database\QueryException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;


class AuthController
{
    public function __construct()
    {
        try {
            $this->set_shop = ShopSettings::getDefault();
        } catch (QueryException $qe) {
            \Log::error("Register SQL error: " . $qe->getMessage());
            return response()->json(["ok" => false, "description" => "Ошибка при регистрации. Попробуйте позже."], 200);
        } catch (QueryException $qe) {
            \Log::error($qe->getMessage());
            return response()->json(["ok" => false, "description" => "Произошла ошибка. Попробуйте позже."], 200);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }

    public function auth_by_link($hash) {

        try {
            $user = User::where('remember_token', $hash)->first();

            $session_token = '';

            if ($user) {
                $session_token = Auth::login($user);
                Member::changeTokenByID($user->id, $user->sid);
//                return response()->json(['ok' => true, 'result' => ['token' => $session_token]]);
            } else {
//                return response()->json(['ok' => false]);
            }

            return view('user.login', ['session_token' => $session_token]);

        } catch (QueryException $qe) {
            \Log::error("Register SQL error: " . $qe->getMessage());
            return response()->json(["ok" => false, "description" => "Ошибка при регистрации. Попробуйте позже."], 200);
        } catch (QueryException $qe) {
            \Log::error($qe->getMessage());
            return response()->json(["ok" => false, "description" => "Произошла ошибка. Попробуйте позже."], 200);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function info()
    {
        try {
            $user = Auth::user();
            if(!$user){return response()->json(['ok' => false, 'description' => 'Unauthenticated'], 401);}

            $shop_currency = ['RUB' => '₽', 'USD' => '$'];

            $main_currency = $this->set_shop->currency;
            $currency_code = $user->currency;
            $currency_symbol = $shop_currency[$user->currency];


            $ref_users = Member::where('rid', $user->id)->count();

            return response()->json([
                'ok' => true,
                'result' => [
                    'id' => $user->id,
                    'tid' => $user->tid,
                    'role_id' => $user->role_id,
                    'email' => $user->email,
                    'username' => $user->username,
                    'balance_main' => Currency::convert($main_currency, $currency_code, $user->balance_main).$currency_symbol,
                    'balance_affiliate' => Currency::convert($main_currency, $currency_code, $user->balance_affiliate).$currency_symbol,
                    'ref_percent' => $user->ref_percent,
                    'ref_users' => $ref_users,
                    'ref_code' => $user->ref_code,
                    'is_ban' => $user->is_ban,
                    // 'remember_token' => $user->remember_token, // SECURITY: removed from API response
                    'email_notify_tickets' => $user->email_notify_tickets,
                    'email_notify_orders' => $user->email_notify_orders,
                    'email_notify_status_changed' => $user->email_notify_status_changed,
                ]
            ]);

        } catch (QueryException $qe) {
            \Log::error("Register SQL error: " . $qe->getMessage());
            return response()->json(["ok" => false, "description" => "Ошибка при регистрации. Попробуйте позже."], 200);
        } catch (QueryException $qe) {
            \Log::error($qe->getMessage());
            return response()->json(["ok" => false, "description" => "Произошла ошибка. Попробуйте позже."], 200);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }

    }


    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'regex:/^[a-zA-Z0-9_.@]+$/', 'min:3', 'max:40', 'string'],
                'password' => 'required|string',
                'h-captcha-response' => config('app.captcha_enabled', false) ? 'required|string' : 'nullable|string',
            ]);

            $validator->setAttributeNames([
                'username' => 'логин или Email',
                'password' => 'пароль',
                'h-captcha-response' => 'капча'
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1001);
                }
            }

            if(strpos($request->username, '@') !== false) {
                $credentials = [
                    'email' => $request->input('username'),
                    'password' => $request->input('password'),
                ];
            } else {
                $credentials = [
                    'username' => $request->input('username'),
                    'password' => $request->input('password'),
                ];
            }

            if (config('app.captcha_enabled', false)) {
                $hcaptchaResp = \Illuminate\Support\Facades\Http::asForm()->post('https://api.hcaptcha.com/siteverify', [
                    'secret' => config('app.hcaptcha_secret_key'),
                    'response' => $request->get('h-captcha-response'),
                ]);
                $hcaptchaData = $hcaptchaResp->json();

                if (!($hcaptchaData['success'] ?? false)) {
                    throw new Exception('Капча не разгадана.');
                }
            }

            $token = Auth::attempt($credentials);

            if (!$token) {
                return response()->json([
                    'ok' => false,
                    'description' => 'Неверный логин или пароль!',
                ], 200);
            }

            $user = Auth::user();

            // Force-reset gate: if an admin has flagged this account for a
            // password reset, block the session and ask the user to use the
            // emailed reset link. Done after Auth::attempt so a wrong
            // password still says "wrong password" — does not leak the flag.
            if ((int) ($user->force_password_reset ?? 0) === 1) {
                Auth::logout();
                return response()->json([
                    'ok'          => false,
                    'description' => 'Ваш пароль сброшен администратором. Проверьте email — мы отправили ссылку для установки нового пароля.',
                    'code'        => 'force_password_reset_required',
                ], 200);
            }

            // Server-side admin gate: when an admin logs in, persist the user
            // ID in the session so AdminWebGuard can return real Blade pages
            // for /<admin>/* and 404 for everyone else. Same-origin XHR/fetch
            // honour the session cookie automatically.
            $minRole = (int) config('admin.min_role_id', 1);
            if ((int) ($user->role_id ?? 0) >= $minRole) {
                $request->session()->put('admin_session_user_id', $user->id);
            } else {
                $request->session()->forget('admin_session_user_id');
            }

            return response()->json([
                'ok' => true,
                'description' => 'Входим в систему',
                'result' => [
                    'token' => $token,
                ]
            ]);

        } catch (QueryException $qe) {
            \Log::error("Register SQL error: " . $qe->getMessage());
            return response()->json(["ok" => false, "description" => "Ошибка при регистрации. Попробуйте позже."], 200);
        } catch (QueryException $qe) {
            \Log::error($qe->getMessage());
            return response()->json(["ok" => false, "description" => "Произошла ошибка. Попробуйте позже."], 200);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }

    public function get_user_ip(){

        $realIp = request()->header('X-Forwarded-For'); // Пробуем получить IP из заголовка X-Forwarded-For

        if (empty($realIp)) {
            $realIp = request()->header('X-Real-IP'); // Если X-Forwarded-For не установлен, пробуем X-Real-IP
        }

        if (empty($realIp)) {
            $realIp = request()->ip(); // Если и X-Forwarded-For, и X-Real-IP пусты, то получаем IP-адрес из Laravel
        }

        $ip = explode(',', $realIp)[0];

        $apiUrl = 'https://ipinfo.io/'.$ip.'/json';

        $ch = curl_init($apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Ошибка cURL: ' . curl_error($ch);
        } else {
            $data = json_decode($response, true);

            if (isset($data['timezone'])) {
                $timeZone = $data['timezone'];
                $isInCIS = in_array($data['country'], ['RU', 'UA', 'KZ', 'BY', 'AM', 'AZ', 'KG', 'MD', 'TJ', 'TM', 'UZ']);
                if ($isInCIS) {
                    $locale = 'RU';
                    $currency = "RUB";
                } else {
                    $locale = 'EN';
                    $currency = "USD";
                }
            } else {
                $timeZone = 'Europe/Moscow';
                $locale = 'RU';
                $currency = 'RUB';
            }
        }
        curl_close($ch);

        return response()->json(['ok' => true, 'ip' => $data['ip'],'timezone' => $timeZone, 'locale' => $locale, 'currency' => $currency]);

    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'regex:/^[a-zA-Z0-9_.@]+$/', 'unique:users', 'min:3', 'max:40', 'email'],
                'password' => 'required|string|min:6',
                'repassword' => 'required|string|min:6',
                'referral_code' => 'nullable|string|min:6|max:20',
                'h-captcha-response' => config('app.captcha_enabled', false) ? 'required|string' : 'nullable|string',
            ]);

            $validator->setAttributeNames([
                'username' => 'Email',
                'password' => 'пароль',
                'repassword' => 'подтвердите пароль',
                'referral_code' => 'код реферала',
                'h-captcha-response' => 'капча',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1001);
                }
            }


            if (config('app.captcha_enabled', false)) {
                $hcaptchaResp = \Illuminate\Support\Facades\Http::asForm()->post('https://api.hcaptcha.com/siteverify', [
                    'secret' => config('app.hcaptcha_secret_key'),
                    'response' => $request->get('h-captcha-response'),
                ]);
                $hcaptchaData = $hcaptchaResp->json();

                if (!($hcaptchaData['success'] ?? false)) {
                    throw new Exception('Капча не разгадана.');
                }
            }

            if($request->password != $request->repassword) {throw new Exception('Пароли не совпадают.');}

            $ref_id = 0;

            if ($request->referral_code != '') {
                $m = Member::getByRefCode($request->referral_code);
                if(!$m){throw new Exception('Реферальный код не найден.');}

                $ref_id = $m->id;
            }

            $shop = Shop::getDefault();

            $username = explode('@', $request->username)[0];
            $username = str_replace('+', '_', $username);
            $existingUsername = User::where('username', $username)->exists();
            $counter = 1;

            while ($existingUsername) {
                $username = $username . $counter;
                $existingUsername = User::where('username', $username)->exists();
                $counter++;
            }

            $user_info = $this->get_user_ip();

            $user = User::create([
                'rid' => $ref_id,
                'tid' => 0,
                'sid' => $shop->id,
                'mid' => 0,
                'email' => $request->username,
                'username' => $username,
                'password' => Hash::make($request->password),
                'tz' => $user_info->getOriginalContent()['timezone'],
//                'locale' => $user_info->getOriginalContent()['locale'],
//                'currency' => $user_info->getOriginalContent()['currency'],
                'locale' => 'RU',
                'currency' => 'RUB',
                'remember_code' => Str::random(32),
                'remember_token' => Str::random(32),
                'balance_main' => 0,
                'balance_affiliate' => 0,
                'ref_percent' => $this->set_shop->ref_percent,
                'ref_code' => Str::random(10),
                'role_id' => 0,
                'is_ban' => 0,
                'is_active' => 0,
                'is_agreement' => 1,
                'email_notify_tickets' => 1,
                'email_notify_orders' => 1,
                'email_notify_status_changed' => 1,
                'tstep' => 0,
                'tdata' => '{}',
                'created_at' => strtotime('NOW')
            ]);

            $token = Auth::login($user);
            return response()->json([
                'ok' => true,
                'description' => 'Входим в систему',
                'result' => [
                    'token' => $token,
                ]
            ]);

        } catch (QueryException $qe) {
            \Log::error("Register SQL error: " . $qe->getMessage());
            return response()->json(["ok" => false, "description" => "Ошибка при регистрации. Попробуйте позже."], 200);
        } catch (QueryException $qe) {
            \Log::error($qe->getMessage());
            return response()->json(["ok" => false, "description" => "Произошла ошибка. Попробуйте позже."], 200);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Invalidate the JWT in the blacklist so AdminWebGuard::reauthenticateViaJwt()
            // cannot resurrect a session from a still-valid cookie. Best-effort: a
            // missing/expired token must not block logout.
            try {
                $token = $request->cookie('session_token')
                    ?: \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::getToken();
                if ($token) {
                    \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($token)->invalidate();
                }
            } catch (\Throwable $e) {
                \Log::info('logout: jwt invalidate skipped: ' . $e->getMessage());
            }

            Auth::logout();
            try {
                $request->session()->forget(['admin_session_user_id', '2fa_passed_at']);
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (\Throwable $e) {}

            return response()
                ->json(['ok' => true, 'description' => 'Выходим из системы'])
                ->withCookie(cookie()->forget('session_token'));

        } catch (QueryException $qe) {
            \Log::error("Register SQL error: " . $qe->getMessage());
            return response()->json(["ok" => false, "description" => "Ошибка при регистрации. Попробуйте позже."], 200);
        } catch (QueryException $qe) {
            \Log::error($qe->getMessage());
            return response()->json(["ok" => false, "description" => "Произошла ошибка. Попробуйте позже."], 200);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function reset_password(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'email' => ['required', 'regex:/^[a-zA-Z0-9_.@]+$/', 'min:3', 'max:40', 'email'],
                'code' => 'nullable|string|min:6|max:6',
                'new_password' => 'nullable|string|min:5|max:100',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message);
                }
            }

            $member = Member::getByEmail($request->email);

            if($member) {
                if ($request->code != '' && $request->new_password == ''){
                    throw new Exception('Не введен новый пароль!');
                }
                if ($request->code == '' && $request->new_password == ''){
                    $code = Str::random(32);
                    $member->remember_code = $code;
                    $member->save();
                    Mail::send('emails.reset-code', ['code' => strtoupper($code)], function ($message) use ($member) {
                        $message->to($member->email, $member->username)
                            ->subject('Код для восстановления пароля');
                    });
                    return response()->json(['ok' => true, 'description' => 'Письмо с кодом отправлено на почту!']);
                } else {
                    if ($request->code == $member->remember_code) {
                        $member->password = Hash::make($request->new_password);
                        $member->save();
                        return response()->json(['ok' => true, 'description' => 'Пароль изменен!']);
                    } else {
                        throw new Exception('Неверный код восстановления!');
                    }
                }
            } else {
                throw new Exception('Такой Email не найден!');
            }

        } catch (QueryException $qe) {
            \Log::error("Register SQL error: " . $qe->getMessage());
            return response()->json(["ok" => false, "description" => "Ошибка при регистрации. Попробуйте позже."], 200);
        } catch (QueryException $qe) {
            \Log::error($qe->getMessage());
            return response()->json(["ok" => false, "description" => "Произошла ошибка. Попробуйте позже."], 200);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function refresh()
    {
        try {
            return response()->json([ 'ok' => true,'description' => 'Токен обновлен','result' => ['token' => Auth::refresh()]]);
        } catch (QueryException $qe) {
            \Log::error("Register SQL error: " . $qe->getMessage());
            return response()->json(["ok" => false, "description" => "Ошибка при регистрации. Попробуйте позже."], 200);
        } catch (QueryException $qe) {
            \Log::error($qe->getMessage());
            return response()->json(["ok" => false, "description" => "Произошла ошибка. Попробуйте позже."], 200);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
}
