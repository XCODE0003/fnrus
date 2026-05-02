<?php

namespace App\Http\Controllers;

use App\Models\ShopSettings;
use Exception;
use App\Models\ChannelSub;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Telegram\Bot\Api;

class ChannelSubController extends Controller
{

    public $user;

    public function __construct()
    {
        try {
            $this->user = Auth::user();
            $this->set_shop = ShopSettings::getDefault();
        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }

    public function fullinfo($id){
        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $channel_sub = ChannelSub::where('sid', $shop->id)->where('cid', $id)->first();
            if (!$channel_sub) {
                throw new Exception('Канал не найден.', 1);
            }

            $result = [
                'title' => $channel_sub->title,
                'link' => $channel_sub->link,
                'is_active' => $channel_sub->is_active,
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }

    public function create(Request $request){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $shop_token = Crypt::decryptString($shop->token);

            $validator = Validator::make($request->all(), [
                'cid' => 'required|int|min:1',
                'title' => 'required|string|min:4|max:100',
                'link' => 'required|string|min:4|max:200',
                'is_active' => 'required|int|min:0|max:1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $response = Http::get('https://api.telegram.org/bot'.$shop_token.'/getChatMember', [
                'chat_id' => '-100'.$request->cid,
                'user_id' => $shop->tid
            ]);

            $data = $response->json();

            if(!$data['ok']){
                throw new Exception('Вы не добавили бота в администраторы канала.', 1);
            }

            $date_now = Carbon::now()->timestamp;

            $sql = [
                'sid' => $shop->id,
                'cid' => $request->cid,
                'title' => $request->title,
                'link' => $request->link,
                'is_active' => $request->is_active,
                'sort' => 0,
                'created_at' => $date_now,
            ];

            $insert_id = ChannelSub::create($sql);

            if ($insert_id) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $request->cid
                    ]
                ]);
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            if(strpos($error, '1062 Duplicate entry') !== false){
                $error = 'Этот канал уже добавлен';
            }
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }


    public function update($cid, Request $request){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $channel = ChannelSub::where('cid', $cid)->where('sid', $shop->id)->first();
            if (!$channel) {
                throw new Exception('Канал не найден.', 1);
            }

            $shop_token = Crypt::decryptString($shop->token);

            $request->merge([
                'cid' => $cid
            ]);

            $validator = Validator::make($request->all(), [
                'cid' => 'required|int|min:100000',
                'title' => 'required|string|min:4|max:100',
                'link' => 'required|string|min:4|max:200',
                'is_active' => 'required|int|min:0|max:1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $response = Http::get('https://api.telegram.org/bot'.$shop_token.'/getChatMember', [
                'chat_id' => '-100'.$request->cid,
                'user_id' => $shop->tid
            ]);

            $data = $response->json();

            if(!$data['ok']){
                throw new Exception('Вы не добавили бота в администраторы канала.', 1);
            }

            $sql = [
                'title' => $request->title,
                'link' => $request->link,
                'is_active' => $request->is_active,
            ];

            ChannelSub::where('cid', $cid)->update($sql);

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено',
                'result' => [
                    'id' => $request->cid
                ]
            ]);

        } catch (Exception $e) {
            $error = $e->getMessage();
            if(strpos($error, '1062 Duplicate entry') !== false){
                $error = 'Этот канал уже добавлен';
            }
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }

    public function sort(Request $request){
        try {
            if($request->filled($request->sort)){
                throw new Exception('Сортировка не определена.');
            }

            foreach ($request->sort as $i => $row) {
                $sql = ['sort' => ++$i];
                ChannelSub::where('cid', intval($row))->update($sql);
            }

            return response()->json([
                'ok' => true,
                'description' => 'Перемещено'
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }

    public function delete($cid){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $channel = ChannelSub::where('cid', $cid)->where('sid', $shop->id)->first();
            if (!$channel) {
                throw new Exception('Канал не найден.', 1);
            }

            $delete = ChannelSub::where('cid', $cid)->delete();

            if($delete) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Удалено'
                ]);
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }
}
