<?php

namespace App\Http\Controllers;

use App\Models\ChannelSub;
use App\Models\ChannelSubSettings;
use App\Models\Shop;
use App\Models\ShopSettings;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class ChannelSubSettingsController extends Controller
{
    public $user;
    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                $this->set_shop = ShopSettings::getDefault();
                return $next($request);
            });
            return false;
        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }
    public function info_button_check(){
        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $channel_settings = ChannelSubSettings::where('sid', $shop->id)->first();
            if (!$channel_settings) {
                throw new Exception('Настройки не найдены.', 1);
            }


            return response()->json(['ok' => true, 'result' => $channel_settings->button_check]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }

    public function update(Request $request){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $channel_settings = ChannelSubSettings::where('sid', $shop->id)->first();
            if (!$channel_settings) {throw new Exception('Настройки не найдены.', 1);}


            $validator = Validator::make($request->all(), [
                'type' => 'required|in:text,columns',
            ]);

            if($request->type == 'text'){
                $validator = Validator::make($request->all(), [
                    'text' => 'required_if:type,text|string|min:4',
                ]);
            }

            if($request->type == 'columns'){
                $validator = Validator::make($request->all(), [
                    'count_columns' => 'required_if:type,columns|integer|min:1|max:3',
                ]);
            }

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            if(!$request->filled($request->text)){
                $sql = ['text' => $request->text];
            }

            if(!$request->filled($request->count_columns)){
                $sql = ['count_columns' => $request->count_columns];
            }

            ChannelSubSettings::where('sid', $shop->id)->update($sql);

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено'
            ]);

        } catch (Exception $e) {
            $error = $e->getMessage();
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }


    public function update_button_check(Request $request){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $channel_settings = ChannelSubSettings::where('sid', $shop->id)->first();
            if (!$channel_settings) {
                throw new Exception('Настройки не найдены.', 1);
            }

            $validator = Validator::make($request->all(), [
                'button_check' => 'required|string|min:4',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $sql = [
                'button_check' => $request->button_check,
            ];

            ChannelSubSettings::where('sid', $shop->id)->update($sql);

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено'
            ]);

        } catch (Exception $e) {
            $error = $e->getMessage();
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }

    public function set_active(){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $channel_settings = ChannelSubSettings::where('sid', $shop->id)->first();
            if (!$channel_settings) {
                throw new Exception('Настройки не найдены.', 1);
            }

            $is_active = 0;
            if($channel_settings->is_active == 0){
                $is_active = 1;
            }

            $sql = [
                'is_active' => $is_active,
            ];

            ChannelSubSettings::where('sid', $shop->id)->update($sql);

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено',
            ]);

        } catch (Exception $e) {
            $error = $e->getMessage();
            if(strpos($error, '1062 Duplicate entry') !== false){
                $error = 'Этот канал уже добавлен';
            }
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }

}
