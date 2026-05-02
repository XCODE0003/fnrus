<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\Member;
use App\Models\RolePermission;
use App\Models\Sender;
use App\Models\ShopSettings;
use Exception;
use App\Models\Product;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Attach;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Telegram\Bot\FileUpload\InputFile;
use Yajra\Datatables\Facades\Datatables;
use Telegram\Bot\Api;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\Log;

class SenderController extends Controller
{
    public $user;
    public $set_shop;
    public function __construct(Request $request)
    {
        try {
            $this->user = Auth::user();
            $this->set_shop = ShopSettings::getDefault();
        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public static function cron_start(){
        try {

            $senders = Sender::getByDate(strtotime(date('Y-m-d H:i')));

            $count = 0;

            foreach ($senders as $s) {
                $count++;
                Sender::changeByID($s->id, ['status' => 4]);
                static::start_sender($s->id);
                Sender::changeByID($s->id, ['status' => 2]);
            }


            return response()->json(['ok' => true, 'time' => strtotime(date('Y-m-d H:i')), 'count' => $count]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }

    public static function start_sender($id){

        try {

            $shop = Shop::getDefault();
            $token = Crypt::decryptString($shop->token);

            $sender = Sender::getByID($shop->id, $id);
            $message_text = str_replace(['</p><p>'], "\r\n", $sender->message);
            $message_text = str_replace(['<p>', '</p>'], '', $message_text);
            $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
            $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

            $chat_ids = Member::getTgAll();

            $client = new Client();

            $promises = [];

            foreach ($chat_ids as $user) {

                $chat_id = $user->tid;

                $ki[$chat_id] = [];
                $buttons = json_decode($sender->buttons, true);

                foreach ($buttons as $b){
                    if(isset($b['url'])) {
                        $ki[$chat_id][] = ["text" => $b['text'], "url" => $b['url']];
                    } else {
                        $ki[$chat_id][] = ["text" => $b['text'], "callback_data" => $b['callback_data']];
                    }
                }

                $kp[$chat_id] = ["inline_keyboard" => array_chunk($ki[$chat_id],1)];

                $encoded_markup = urlencode(json_encode($kp[$chat_id]));

                if($sender->forward_link != '') {

                    $param_post = explode('/', $sender->forward_link);

                    if (strpos($param_post[3], 'c') !== false) {
                        $from_chat_id = '-100' . $param_post[4];
                        $message_id = $param_post[5];
                    } else {
                        $from_chat_id = '@' . $param_post[3];
                        $message_id = $param_post[4];
                    }

                    $url_request = 'https://api.telegram.org/bot' . $token . '/forwardMessage?chat_id=' . $chat_id . '&from_chat_id='.$from_chat_id.'&message_id='.$message_id;
                    echo $url_request;
                } else {
                    if ($sender->image == '') {
                        $url_request = 'https://api.telegram.org/bot' . $token . '/sendMessage?chat_id=' . $chat_id . '&text=' . $message_text . '&disable_web_page_preview=' . boolval($sender->disable_web_page_preview) . '&parse_mode=HTML&reply_markup=' . $encoded_markup;
                    } else {
                        $url_request = 'https://api.telegram.org/bot' . $token . '/sendPhoto?chat_id=' . $chat_id . '&photo=' . config('app.url') . '/i' . $sender->image . '&caption=' . $message_text . '&has_spoiler=' . boolval($sender->has_spoiler) . '&disable_web_page_preview=' . boolval($sender->disable_web_page_preview) . '&parse_mode=HTML&reply_markup=' . $encoded_markup;
                    }
                }

                $request = new \GuzzleHttp\Psr7\Request('GET', $url_request);

                    $promises[] = $client->sendAsync($request)->then(
                    function (ResponseInterface $res) use ($id, $chat_id) {
                        Sender::where('id', $id)->increment('count_success', 1);
                        echo $res->getStatusCode().'<br>';
//                        Log::info('Message sent to ' . $chat_id . '. Response status: ' . $res->getStatusCode());
                    },
                    function (\Exception $e) use ($id, $chat_id) {
                        Sender::where('id', $id)->increment('count_fail', 1);
                        echo $e->getMessage().'<br>';
//                        Log::error('Error sending message to ' . $chat_id . '. ' . $e->getMessage());
                    }
                );

                if (count($promises) >= 30) {
                    $eachPromise = new Promise\EachPromise($promises, [
                        'concurrency' => 30,
                        'rejected' => function ($reason) {
                        },
                    ]);

                    // Ожидание выполнения всех обещаний
                    $eachPromise->promise()->wait();

                    // Очищаем массив промисов.
                    $promises = [];

                    // Пауза на 1 секунду.
                    sleep(1);
                }
            }

            // Разрешаем остальные промисы.
            if (count($promises) > 0) {
                $eachPromise = new Promise\EachPromise($promises, [
                    'concurrency' => 30,
                    'rejected' => function ($reason) {
                        // Обработка ошибок
                    },
                ]);

                $eachPromise->promise()->wait();
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function fullinfo($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'senders.info')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $sender = Sender::getByID($shop->id, $id);
            if (!$sender) {throw new Exception('Рассылка не найдена.', 1);}

            $image = '';
            if($sender->image != ''){
                $image = 'i'.$sender->image;
            }

            $result = [
                'title' => $sender->title,
                'message' => $sender->message,
                'disable_web_page_preview' => $sender->disable_web_page_preview,
                'has_spoiler' => $sender->has_spoiler,
                'forward_link' => $sender->forward_link,
                'buttons' => json_decode($sender->buttons, true),
                'image' => $image,
                'type' => $sender->type,
                'status' => $sender->status,
                'started_at' => $this->formatDateTimeZone($sender->started_at,$this->user->tz,'fullinfo'),
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function delete($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'senders.delete')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $sender = Sender::getByID($shop->id, $id);
            if (!$sender) {throw new Exception('Рассылка не найдена.', 1);}

            $delete = Sender::deleteByID($shop->id, $id);
            if($delete) {return response()->json(['ok' => true, 'description' => 'Удалено']);}
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function all(Request $request)
    {
        try {
            $start = $request->input('start');
            $length = $request->input('length');
            $search_term = $request->input('search.value');
            $order_column = $request->input('order.0.column');
            $order_dir = $request->input('order.0.dir'); // направление сортировки (asc или desc)
            $column_name = $request->input('columns.' . $order_column . '.data'); // имя столбца для сортировки

            $status = $request->input('status');

            // if($column_name == 'member'){$column_name = 'tid';}

            $access = RolePermission::getByPermission($this->user->role_id, 'senders.all')->allow;
            if (!$access) {
                throw new Exception('Доступ запрещен.');
            }

            $shop_default = Shop::getDefault();

            if ($column_name && $order_dir) {
                $senders = Sender::orderBy($column_name, $order_dir);
            } else {
                $senders = Sender::orderBy('started_at', 'DESC');
            }

            $total_count = Sender::count();

            if($status != null){
                $senders = $senders->where('status', $status);
            }

            if ($search_term) {
                $senders = $senders->where(function($query) use ($search_term) {
                    $query->where('title', 'LIKE', '%' . $search_term . '%');

                    $shop = Shop::where('username', 'LIKE', '%' . $search_term . '%')->first();

                    if ($shop) {
                        $query->orWhere('sid', $shop->id);
                    }
                });
            }


            $filtered_count = $senders->count();
            $senders = $senders->offset($start)->limit($length)->get();

            $all = [];

            foreach ($senders as $s) {

                $block_status = 0;

                switch ($s->status) {
                    case 1;
                        $block_status = '<span class="badge bg-warning px-2" style="color: #000"><i class="far fa-clock"></i> Ожидает запуска</span>';
                        break;
                    case 2;
                        $block_status = '<span class="badge bg-success px-2" style="color: #000"><i class="far fa-check"></i> Выполнено</span>';
                        break;
                    case 3;
                        $block_status = '<span class="badge bg-secondary px-2" style="color: #000">Черновик</span>';
                        break;
                    case 4;
                        $block_status = '<span class="badge bg-primary px-2" style="color: #000">Выполняется</span>';
                        break;
                }

                $progress = $s->count_all . ' / <span class="text-success">' . $s->count_success . '</span> / <span class="text-danger">' . $s->count_fail . '</span>';

                if ($s->type == 1) {
                    $type = 'Сообщение';
                }
                if ($s->type == 2) {
                    $type = 'Репост';
                }

                $all[] = [
                    'id' => $s->id,
                    'icon' => '<i class="far fa-envelope"></i>',
                    'title' => $s->title,
                    'type' => $type,
                    'status' => $block_status,
                    'progress' => $progress,
                    'started_at' => $this->formatDateTimeZone($s->started_at, $this->user->tz, ''),
                    'block_edit' => '<a data-id="' . $s->id . '" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#editSender"><i class="far fa-edit fa-xl"></i></a>',
                    'block_delete' => '<a onclick="deleteSender(' . $s->id . ');" title="Удалить" class="text-danger" href="javascript:;"> <i class="far fa-trash fa-xl"></i></a>'
                ];
            }

            return response()->json(['data' => $all, 'recordsTotal' => $total_count, 'recordsFiltered' => $filtered_count]);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function check(Request $request) {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'senders.check')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $token = Crypt::decryptString($shop->token);
            $tg = new Api($token);

            if($request->get('type') == 1) {
                $validator = Validator::make($request->all(), [
                    'type' => 'required|int|between:1,2',
                    'title' => 'nullable|string|min:4|max:255',
                    'message' => 'required|string|min:4|max:4096',
                    'image' => 'nullable|string|min:10|max:255',
                    'buttons' => 'nullable|string',
                    'disable_web_page_preview' => 'required|int|between:0,1',
                    'has_spoiler' => 'required|int|between:0,1',
                ]);
            }

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            if($request->get('type') == 1) {

                $image = '';

                if(!$request->filled($request->image)){
                    $image = substr($request->image, 1);
                    $r = Attach::where('id', $image)->first();
                    if(!$r){throw new Exception('Изображение не найдено.', 1);}
                }

                if($request->get('message') == '<p><br></p>'){
                    throw new Exception('Сообщение не должно быть пустое');
                }

                $message_text = str_replace(['</p><p>'], "\r\n", $request->get('message'));
                $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                if($image == '' && mb_strlen(strip_tags($message_text)) > config('app.tg_limit_text_no_image')){
                    throw new Exception('Сообщение не должно превышать '.config('app.tg_limit_text_no_image').' символов.');
                }
                if($image != '' && mb_strlen(strip_tags($message_text)) > config('app.tg_limit_text_image')){
                    throw new Exception('Сообщение не должно превышать '.config('app.tg_limit_text_image').' символов.');
                }

                $buttons = [];

                foreach (json_decode($request->get('buttons'), true) as $b) {
//
//                    if (!preg_match("#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#", $b['url'], $match)) {
//                        throw new Exception('Неверный формат ссылки у кнопки');
//                    }

                    if(isset($b['url'])) {
                        $buttons[] = ['text' => $b['text'], 'url' => $b['url']];
                    } else {
                        $buttons[] = ['text' => $b['text'], 'callback_data' => $b['callback_data']];
                    }
                }

                $kp = json_encode(["inline_keyboard" => array_chunk($buttons, 1)]);


                if($request->get('image') != '') {
                    $tg->sendPhoto(['chat_id' => $this->set_shop->notify_target_id, 'photo' => new InputFile(Attach::getPathById($image)), 'has_spoiler' => $request->get('has_spoiler'), 'caption' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => $request->get('disable_web_page_preview'), "reply_markup" => $kp]);
                } else {
                    $tg->sendMessage(['chat_id' => $this->set_shop->notify_target_id, 'text' => $message_text, "disable_web_page_preview" => $request->get('disable_web_page_preview'), "parse_mode" => "HTML", "reply_markup" => $kp]);
                }
            }

            return response()->json(['ok' => true, 'description' => 'Проверено']);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function create(Request $request) {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'senders.add')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            date_default_timezone_set('Europe/Moscow');

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            if($request->get('type') == 1) {
                $validator = Validator::make($request->all(), [
                    'type' => 'required|int|between:1,2',
                    'title' => 'nullable|string|min:4|max:255',
                    'message' => 'required|string|min:4|max:4096',
                    'image' => 'nullable|string|min:10|max:255',
                    'buttons' => 'nullable|string',
                    'disable_web_page_preview' => 'required|int|between:0,1',
                    'has_spoiler' => 'required|int|between:0,1',
                    'type_time' => 'required|int|between:0,1'
                ]);
            }

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $title = 'Без названия';
            $buttons = [];
            $message = '';
            $disable_web_page_preview = 0;
            $has_spoiler = 0;
            $forward_link = '';
            $image = '';

            if($request->get('type') == 1) {

                if(!$request->filled($request->image)){
                    $image = substr($request->image, 1);
                    $r = Attach::where('id', $image)->first();
                    if(!$r){throw new Exception('Изображение не найдено.', 1);}
                }

                if($request->get('message') == '<p><br></p>'){
                    throw new Exception('Сообщение не должно быть пустое');
                }

                $message_text = str_replace(['</p><p>'], "\r\n", $request->get('message'));
                $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                if($image == '' && mb_strlen(strip_tags($message_text)) > config('app.tg_limit_text_no_image')){
                    throw new Exception('Сообщение не должно превышать '.config('app.tg_limit_text_no_image').' символов.');
                }

                if($image != '' && mb_strlen(strip_tags($message_text)) > config('app.tg_limit_text_image')){
                    throw new Exception('Сообщение не должно превышать '.config('app.tg_limit_text_image').' символов.');
                }

                foreach (json_decode($request->get('buttons'), true) as $b) {

                    if(isset($b['url'])) {
                        $buttons[] = ['text' => $b['text'], 'url' => $b['url']];
                    } else {
                        $buttons[] = ['text' => $b['text'], 'callback_data' => $b['callback_data']];
                    }
                }
            }

            if($request->get('type_time') == 0){
                $started_at = date('Y-m-d\TH:i');
            }

            if($request->get('type_time') == 1){
                $date_started = date('Y-m-d H:i:s',strtotime($request->get('started_at')));
                $started_at = $this->convertTimeZone($date_started, $this->user->tz, 'Europe/Moscow');
            }

            $dateFormat = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/';
            if (!preg_match($dateFormat, $started_at)) {
                throw new Exception('Неверный формат даты запуска');
            }

            $started_at = strtotime(date($started_at).'+1 minutes');
            if($started_at < strtotime('NOW')){
                throw new Exception('Неверная дата запуска');
            }

            if($request->get('message')){$message = $request->get('message');}
            if($request->get('title')){$title = $request->get('title');}
            if($request->get('disable_web_page_preview')){$disable_web_page_preview = $request->get('disable_web_page_preview');}
            if($request->get('has_spoiler')){$has_spoiler = $request->get('has_spoiler');}
            if($request->get('forward_link')){$forward_link = $request->get('forward_link');}

            $count_all = Member::getCountAll();

            $sql = [
                "sid" => $shop->id,
                "title" => $title,
                "forward_link" => $forward_link,
                "message" => $message,
                "image" => $image,
                "buttons" => json_encode($buttons),
                "disable_web_page_preview" => $disable_web_page_preview,
                "has_spoiler" => $has_spoiler,
                "count_all" => $count_all,
                "count_success" => 0,
                "count_fail" => 0,
                "status" => 1,
                "type" => $request->get('type'),
                "started_at" => $started_at,
                "updated_at" => strtotime('NOW'),
                "created_at" => strtotime('NOW'),
            ];

            $sender_id = Sender::insertGetId($sql);

            if($sender_id){
                return response()->json([
                    'ok' => true,
                    'description' => 'Рассылка создана',
                    'result' => ['id' => $sender_id]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'line' => $e->getLine(), 'file' => $e->getFile(), 'description' => $e->getMessage()]);
        }
    }


    public function update($id, Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'senders.edit')->allow;
            if (!$access) {
                throw new Exception('Access Denied');
            }

            date_default_timezone_set('Europe/Moscow');

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $request->merge([
                'id' => $id,
            ]);

            if($request->get('type') == 1) {
                $validator = Validator::make($request->all(), [
                    'id' => 'required|int|min:1',
                    'type' => 'required|int|between:1,2',
                    'title' => 'nullable|string|min:4|max:255',
                    'message' => 'required|string|min:4|max:4096',
                    'image' => 'nullable|string|min:10|max:255',
                    'buttons' => 'nullable|string',
                    'disable_web_page_preview' => 'required|int|between:0,1',
                    'has_spoiler' => 'required|int|between:0,1',
                    'type_time' => 'required|int|between:0,1'
                ]);
            }


            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $title = 'Без названия';
            $buttons = [];
            $message = '';
            $disable_web_page_preview = 0;
            $has_spoiler = 0;
            $forward_link = '';
            $image = '';

            if($request->get('type') == 1) {

                if(!$request->filled($request->image)){
                    $image = substr($request->image, 1);
                    $r = Attach::where('id', $image)->first();
                    if(!$r){throw new Exception('Изображение не найдено.', 1);}
                }

                if($request->get('message') == '<p><br></p>'){
                    throw new Exception('Сообщение не должно быть пустое');
                }

                $message_text = str_replace(['</p><p>'], "\r\n", $request->get('message'));
                $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                if($image == '' && mb_strlen(strip_tags($message_text)) > config('app.tg_limit_text_no_image')){
                    throw new Exception('Сообщение не должно превышать '.config('app.tg_limit_text_no_image').' символов.');
                }

                if($image != '' && mb_strlen(strip_tags($message_text)) > config('app.tg_limit_text_image')){
                    throw new Exception('Сообщение не должно превышать '.config('app.tg_limit_text_image').' символов.');
                }

                foreach (json_decode($request->get('buttons'), true) as $b) {

                    if(isset($b['url'])) {
                        $buttons[] = ['text' => $b['text'], 'url' => $b['url']];
                    } else {
                        $buttons[] = ['text' => $b['text'], 'callback_data' => $b['callback_data']];
                    }
                }
            }

            if($request->get('type_time') == 0){
                $started_at = date('Y-m-d\TH:i');
            }

            if($request->get('type_time') == 1){
                $date_started = date('Y-m-d H:i:s',strtotime($request->get('started_at')));
                $started_at = $this->convertTimeZone($date_started, $this->user->tz, 'Europe/Moscow');
            }

            $dateFormat = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/';
            if (!preg_match($dateFormat, $started_at)) {
                throw new Exception('Неверный формат даты запуска');
            }

            $started_at = strtotime(date($started_at).'+1 minutes');
            if($started_at < strtotime('NOW')){
                throw new Exception('Неверная дата запуска');
            }

            if($request->get('message')){$message = $request->get('message');}
            if($request->get('title')){$title = $request->get('title');}
            if($request->get('disable_web_page_preview')){$disable_web_page_preview = $request->get('disable_web_page_preview');}
            if($request->get('has_spoiler')){$has_spoiler = $request->get('has_spoiler');}
            if($request->get('forward_link')){$forward_link = $request->get('forward_link');}

            $count_all = Member::getCountAll();

            $sql = [
                "title" => $title,
                "forward_link" => $forward_link,
                "message" => $message,
                "image" => $image,
                "buttons" => json_encode($buttons),
                "disable_web_page_preview" => $disable_web_page_preview,
                "has_spoiler" => $has_spoiler,
                "count_all" => $count_all,
                "count_success" => 0,
                "count_fail" => 0,
                "status" => 1,
                "started_at" => $started_at,
                "updated_at" => strtotime('NOW'),
            ];

            $update = Sender::where('sid', $shop->id)
                ->where('id', $id)
                ->update($sql);

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

    public function formatDateTimeZone($date,$to,$format)
    {

        $dateUTC = Carbon::now();
        $datePST = Carbon::now();
        $datePST->setTimezone($to);

        $start  = new Carbon($dateUTC);
        $end    = new Carbon($datePST);

        $seconds = strtotime($end) - strtotime($start);
        $hours = $seconds / 60 /  60;

        $carbon = Carbon::createFromDate(date('Y-m-d H:i:s',$date), $to);
        $carbon->addHours($hours);

        if($format == 'fullinfo'){
            return $carbon->format('Y-m-d\TH:i');
        }

        return $carbon->format('d.m.Y в H:i');

    }
    function convertTimeZone(string $date, string $fromTimeZone, string $toTimeZone): string {
        $dateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date, $fromTimeZone);
        $dateTime->setTimezone($toTimeZone);
        return $dateTime->format('Y-m-d\TH:i');
    }


}
