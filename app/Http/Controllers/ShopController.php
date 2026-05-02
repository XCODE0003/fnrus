<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\ShopSettings;
use Exception;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Telegram\Bot\Api;

class ShopController extends Controller
{

    public $user;

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                return $next($request);
            });
            $this->set_shop = ShopSettings::getDefault();
        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function setStatus()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.bot_status')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $token = Crypt::decryptString($shop->token);

            $tg = new Api($token);
            $status = $tg->getWebhookInfo();

            if($status->url == ''){
                $tg->setWebhook(['url' => config('app.url_wh').$shop->id, 'max_connections' => 100, 'drop_pending_updates' => true]);
                $new_status = 1;
                $alert = 'launched';
            } else {
                $tg->deleteWebhook();
                $new_status = 0;
                $alert = 'stopped';
            }

            $shop->status = $new_status;
            $shop->save();

            return response()->json(['ok' => true, 'status' => $new_status, 'description' => 'Shop '.$alert], 200);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }

    }

    public function info()
    {
        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            return response()->json([
                'ok' => true,
                'result' => [
                    'id' => $shop->tid,
                    'username' => $shop->username,
                    'avatar' => '',
                    'token' => mb_substr(Crypt::decryptString($shop->token), 0, -20).'********************',
                    'status' => $shop->status,
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }

    }

    public function get_token()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.secret_token')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            return response()->json([
                'ok' => true,
                'result' => [
                    'token' => Crypt::decryptString($shop->token),
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function update_token(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.secret_token')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'token' => 'required|string|min:20|max:250',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Магазин не найден.', 1);
            }

            $tg = new Api($request->token);
            $response = $tg->getMe();
            $tid = $response->getId();


            $shop->tid = $tid;
            $shop->username = $response->getUsername();
            $shop->token = Crypt::encryptString($request->token);
            $shop->updated_at = strtotime('NOW');
            $shop->save();

            $tg->setWebhook(['url' => config('app.url_wh').$shop->id, 'max_connections' => 100, 'drop_pending_updates' => true]);

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено',
            ]);

        } catch (Exception $e) {

            $err = $e->getMessage();

            if ($err == 'Unauthorized'){
                $err = 'Невалидный токен';
            }

            if ($err == 'Not Found'){
                $err = 'Неверный формат токена';
            }

            return response()->json(['ok' => false, 'description' => $err], 200);
        }
    }

}
