<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\ShopSettings;
use Exception;
use App\Models\ButtonSettings;
use App\Models\Button;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ButtonController extends Controller
{
    public $user;
    public function __construct()
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                return $next($request);
            });
            $this->set_shop = ShopSettings::getDefault();
        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }

    public function all($sid){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $buttons = Button::getByShopID($sid);

            $all = [];

            foreach ($buttons as $b) {
                $all[] = $b->title;
            }

            return response($all);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }

    }


    public function fullinfo($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $button = Button::where('sid', $shop->id)->where('id', $id)->first();
            if (!$button) {throw new Exception('Кнопка не найдена.', 1);}

            $image = '';
            if($button->image != ''){
                $image = 'i'.$button->image;
            }

            $result = [
                'title' => $button->title,
                'text' => $button->text,
                'image' => $image,
                'disable_web_page_preview' => $button->disable_web_page_preview,
                'has_spoiler' => $button->image_spoiler,
                'type' => $button->type,
                'visible' => $button->visible,
                'buttons' => json_decode($button->buttons, true)
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }


    public function create(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $textMaxLength = $request->has('image') && !empty($request->input('image')) ? config('app.tg_limit_text_image') : config('app.tg_limit_text_no_image');

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|min:4|max:100',
                'text' => "required|string|min:4|max:$textMaxLength",
                'image' => 'nullable|string|min:4|max:200',
                'disable_web_page_preview' => 'required|int|min:0|max:1',
                'has_spoiler' => 'required|int|min:0|max:1',
                'visible' => 'required|int|min:0|max:1',
                'buttons' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $buttons = [];
            $image = '';
            if(!$request->filled($request->image)){
                $image = substr($request->image, 1);
                $r = DB::table('attachments')->where('id', $image)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }

            foreach (json_decode($request->get('buttons'), true) as $b) {
                if(isset($b['url'])) {
                    $buttons[] = ['text' => $b['text'], 'url' => $b['url']];
                } else {
                    $buttons[] = ['text' => $b['text'], 'callback_data' => $b['callback_data']];
                }
            }

            $date_now = Carbon::now()->timestamp;

            $sql = [
                'sid' => $shop->id,
                'title' => $request->title,
                'text' => $request->text,
                'image' => $image,
                'disable_web_page_preview' => $request->disable_web_page_preview,
                'image_spoiler' => $request->has_spoiler,
                'buttons' => json_encode($buttons),
                'type' => 2,
                'sort' => 0,
                'visible' => $request->visible,
                'updated_at' => $date_now,
                'created_at' => $date_now,
            ];

            $insert_id = Button::create($sql);

            if ($insert_id) {
                return response()->json(['ok' => true, 'description' => 'Сохранено', 'result' => ['id' => $insert_id]]);
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }

    public function update($id, Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $button = Button::where('sid', $shop->id)->where('id', $id)->first();
            if (!$button) {throw new Exception('Кнопка не найдена.', 1);}

            $request->merge(['id' => $id]);

            $textMaxLength = $request->has('image') && !empty($request->input('image')) ? config('app.tg_limit_text_image') : config('app.tg_limit_text_no_image');

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|min:4|max:100',
                'text' => "required|string|min:4|max:$textMaxLength",
                'image' => 'nullable|string|min:4|max:200',
                'disable_web_page_preview' => 'required|int|min:0|max:1',
                'has_spoiler' => 'required|int|min:0|max:1',
                'visible' => 'required|int|min:0|max:1',
                'buttons' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $buttons = [];
            $image = '';
            if(!$request->filled($request->image)){
                $image = substr($request->image, 1);
                $r = DB::table('attachments')->where('id', $image)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }
            foreach (json_decode($request->get('buttons'), true) as $b) {
                if(isset($b['url'])) {
                    $buttons[] = ['text' => $b['text'], 'url' => $b['url']];
                } else {
                    $buttons[] = ['text' => $b['text'], 'callback_data' => $b['callback_data']];
                }
            }

            $date_now = Carbon::now()->timestamp;

            $sql = [
                'title' => $request->title,
                'text' => $request->text,
                'image' => $image,
                'disable_web_page_preview' => $request->disable_web_page_preview,
                'image_spoiler' => $request->has_spoiler,
                'buttons' => json_encode($buttons),
                'visible' => $request->visible,
                'updated_at' => $date_now,
            ];

            Button::where('sid', $shop->id)->where('id', $id)->update($sql);

            return response()->json(['ok' => true, 'description' => 'Сохранено']);

        } catch (Exception $e) {
            $error = $e->getMessage();
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }

    public function sort(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            if($request->filled($request->sort)){throw new Exception('Сортировка не определена.');}

            foreach ($request->sort as $i => $row) {
                $sql = ['sort' => ++$i];
                Button::where('id', intval($row))->update($sql);
            }

            return response()->json(['ok' => true, 'description' => 'Перемещено']);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function delete($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $button = Button::where('id', $id)->where('sid', $shop->id)->first();
            if (!$button) {throw new Exception('Кнопка не найден.', 1);}

            $delete = Button::where('id', $id)->delete();

            if($delete) {
                return response()->json(['ok' => true, 'description' => 'Удалено']);
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()]);
        }
    }

}
