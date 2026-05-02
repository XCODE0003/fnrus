<?php

namespace App\Http\Controllers;

use App\Models\ButtonSettings;
use App\Models\RolePermission;
use Exception;
use App\Models\Text;
use App\Models\Shop;
use App\Models\Button;
use App\Models\ChannelSub;
use App\Models\ChannelSubSettings;
use App\Models\ShopSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class TextController extends Controller
{
    public $user;

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                return $next($request);
            });
        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }
    public function fullinfo(){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Магазин не найден.', 1);
            }

            $t = Text::where('sid', $shop->id)->get();

            $result = [];

            foreach ($t as $item) {

                $buttons = [];
                foreach (json_decode($item->buttons, true) as $b) {
                    if(!empty($b['callback_data'])){
                        $buttons[] = ['text' => $b['text'], 'callback_data' => $b['callback_data']];
                    } else if(!empty($b['url'])){
                        $buttons[] = ['text' => $b['text'], 'url' => $b['url']];
                    }
                }
                $image = '';
                if($item->image != ''){
                    $image = 'i'.$item->image;
                }
                $result[$item->type] = ['text' => $item->text, 'image' => $image, 'disable_web_page_preview' => $item->disable_web_page_preview, 'is_spoiler' => $item->is_spoiler, 'buttons' => $buttons, 'is_active' => $item->is_active];
            }

            $channels = [];

            $channels_all = ChannelSub::getAll($shop->id);

            foreach ($channels_all as $c) {
                $channels[] = ['cid' => $c->cid, 'title' => $c->title, 'link' => $c->link, 'is_active' => $c->is_active];
            }

            $channel_settings = ChannelSubSettings::getByShopID($shop->id);

            $buttons_all = Button::getByShopID($shop->id);

            $result['channels']['count'] = count($channels_all);
            $result['channels']['columns'] = $channel_settings->count_columns;
            $result['channels']['all'] = $channels;

            $result['channels']['settings']['text'] = $channel_settings->text;
            $result['channels']['settings']['button_check'] = $channel_settings->button_check;

            $result['channels']['settings']['is_active'] = $channel_settings->is_active;
            $result['buttons']['count'] = count($buttons_all);
            $result['buttons']['columns'] = ButtonSettings::getByShopID($shop->id)->count_columns;
            $result['buttons']['all'] = $buttons_all;

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }


    public function update($type, Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $request->merge([
                'type' => $type
            ]);

            $validator = Validator::make($request->all(), [
                'type' => 'required|string|min:4|max:100',
                'text' => 'required|string|min:4|max:1024',
                'disable_web_page_preview' => 'required|int|min:0|max:1',
                'is_spoiler' => 'required|int|min:0|max:1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }
//
//            if($request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_no_image')){
//                throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_no_image').' символов.');
//            }
//
//            if(!$request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_image')){
//                throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_image').' символов.');
//            }

            $image = '';
            $buttons = '{}';

            if(!$request->filled($request->buttons)){$buttons = $request->buttons;}
            if(!$request->filled($request->image)){
                $image = substr($request->image, 1);
                $r = DB::table('attachments')->where('id', $image)->first();
                if(!$r){throw new Exception('Изображение не найдено!', 1);}
            }

            $shop = Shop::getDefault();
            if(!$shop){
                throw new Exception('Магазин не найден.', 1);
            }

            $t = Text::where('sid', $shop->id)->where('type', $request->type)->first();
            if(!$t){
                throw new Exception('Текст не найден.', 1);
            }

            $date_now = strtotime('NOW');

            if($request->image != '') {
                $is_spoiler = $request->is_spoiler;
            } else {
                $is_spoiler = 0;
            }

            $sql = [
                'text' => $request->text,
                'image' => $image,
                'disable_web_page_preview' => $request->disable_web_page_preview,
                'is_spoiler' => $is_spoiler,
                'buttons' => $buttons,
                'type' => $request->type,
                'updated_at' => $date_now,
                'created_at' => $date_now
            ];

            $update = Text::where('sid', $shop->id)->where('type', $request->type)->update($sql);
            if ($update) {
                return response()->json(['ok' => true, 'description' => 'Сохранено']);
            }

        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function set_active($type){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if (!$shop) {throw new Exception('Магазин не найден.', 1);}

            $text = Text::where('sid', $shop->id)->where('type', $type)->first();
            if (!$text) {throw new Exception('Такое соодбщение не найдено.', 1);}

            $is_active = 0;
            if($text->is_active == 0){
                $is_active = 1;
            }

            $sql = ['is_active' => $is_active];

            Text::where('sid', $shop->id)->where('type', $type)->update($sql);

            return response()->json(['ok' => true, 'description' => 'Сохранено']);

        } catch (Exception $e) {
            $error = $e->getMessage();
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }
}
