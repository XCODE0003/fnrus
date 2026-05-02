<?php
namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\RolePermission;
use App\Models\ShopSettings;
use App\Models\TicketMessage;
use Exception;
use App\Models\Attach;
use App\Models\Shop;
use App\Models\Ticket;
use App\Models\TicketSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class TicketController extends Controller
{
    public $user;
    public $type;
    public $ticket_auto_close_date = '+24 hours';

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                $this->type = Route::current()->parameter('type');
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


     public function all(Request $request) {

        try {
            $start = $request->input('start');
            $length = $request->input('length');
            $search_term = $request->input('search.value');
            $order_column = $request->input('order.0.column');
            $order_dir = $request->input('order.0.dir'); // направление сортировки (asc или desc)
            $column_name = $request->input('columns.' . $order_column . '.data');
            $status = $request->input('status');
            $subject_id = $request->input('subject_id');

            if($column_name == 'user'){$column_name = 'user_id';}

            $access = RolePermission::getByPermission($this->user->role_id, 'tickets.all')->allow;
            if (!$access) {
                throw new Exception('Доступ запрещен.');
            }

            $shop_default = Shop::getDefault();

            $tickets = Ticket::query();

            $column_name = $column_name ?: 'last_answer_at';
            $order_dir = $order_dir ?: 'desc';

            $tickets = $tickets->orderBy($column_name, $order_dir);

            $total_count = Ticket::count();

            if($status != null){
                $tickets = $tickets->where('status', $status);
            }

            if($subject_id != null){
                $tickets = $tickets->where('subject_id', $subject_id);
            }

            if ($search_term) {
                $tickets = $tickets->where(function($query) use ($search_term) {
                    $member = Member::searchByTID($search_term);
                    if($member){
                        $query->where('user_id', 'LIKE', $member->id);
                    }                
                });
            }

            $filtered_count = $tickets->count();
            $tickets = $tickets->offset($start)->limit($length)->get();

            $all = [];

            foreach ($tickets as $t) {

                $subject_title = TicketSubject::getByID($t->subject_id)->title;
                $member = Member::getByIDFromTicket($t->user_id);
                if ($member) {
                    if ($member->username == '') {
                        $block_user = 'ID: ' . $t->user_id;
                    } else {
                        $block_user = $member->username;
                    }
                } else {
                    $block_user = 'ID: ' . $t->user_id;
                }


                if ($t->status == 0){$status = '<span class="badge bg-warning px-2" style="color: #000"><i class="far fa-clock"></i> Ожидает</span>';}
                if ($t->status == 1){$status = '<span class="badge bg-success px-2" style="color: #000"><i class="far fa-check"></i> Решено</span>';}
                if ($t->status == 2){$status = '<span class="badge bg-secondary px-2" style="color: #000"><i class="far fa-check"></i> Отвечен</span>';}


                $block_key = 'blocked_user_' . $t->user_id;

                $block_blocked = '<a href="javascript:;" onclick="ticketBlockedMemberByID('.$t->id.');return false;" title="Заблокировать"><i class="far fa-lock-alt fa-xl text-danger"></i></a>';

                if(Cache::get($block_key)){
                    $block_blocked = '<a href="javascript:;" onclick="ticketBlockedMemberByID('.$t->id.');return false;" title="Разблокировать"><i class="far fa-lock-open-alt fa-xl text-danger"></i></a>';
                }

                $all[] = [
                    'id' => $t->id,
                    'icon' => '<i class="far fa-file mr-1"></i>',
                    'subject' => $subject_title,
                    'user' => $block_user,
                    'status' => $status,
                    'last_answer_at' => date('d.m.Y H:i', $t->last_answer_at),
                    'block_dialog' => '<a data-id="'.$t->id.'" href="javascript:;" title="Открыть диалог" data-toggle="modal" data-target="#chatTicket"><i class="far fa-comment-dots fa-xl"></i></a>',
                    'block_move' => '<a data-id="'.$t->id.'" data-subject-id="'.$t->subject_id.'" href="javascript:;" title="Перенести тикет" data-toggle="modal" data-target="#ticketMove"><i class="far fa-suitcase-rolling fa-xl"></i></a>',
                    'block_edit' => '<a data-id="'.$t->id.'" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#changeTicket"><i class="far fa-edit fa-xl"></i></a>',
                    'block_blocked' => $block_blocked,
                ];
            }

            return response()->json(['data' => $all, 'recordsTotal' => $total_count, 'recordsFiltered' => $filtered_count]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage(), 'line' => $e->getLine()], 200);
        }
    }

    public function blocked_member($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'tickets.all')->allow;
            if (!$access) {
                throw new Exception('Доступ запрещен.');
            }

            $ticket = Ticket::find($id);
            if (!$ticket) {throw new Exception('Тикет не найден.', 1);}

            $block_key = 'blocked_user_' . $ticket->user_id;

            $decaySeconds = 24 * 60 * 60;

            if(!Cache::has($block_key) && !Cache::get($block_key)){
                Cache::put($block_key, true, $decaySeconds);
                $end_notify = 'заблокирован';
            } else {
                Cache::forget($block_key);
                $end_notify = 'разблокирован';
            }

            return response()->json([
                'ok' => true,
                'description' => 'Пользователь '.$end_notify,
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }


    public function move($id, Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'tickets.all')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $validator = Validator::make($request->all(), [
                'new_subject_id' => 'required|int|min:1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $ticket = Ticket::find($id);
            if (!$ticket) {throw new Exception('Тикет не найден.', 1);}

            $ticket->subject_id = $request->get('new_subject_id');
            $ticket->save();

            return response()->json([
                'ok' => true,
                'description' => 'Тикет перемещен',
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }


    public function list()
    {
        try {
            $tickets = Ticket::where('user_id', $this->user->id)->orderByDesc('last_answer_at')->get();

            $all = [];

            foreach ($tickets as $ticket) {

                $title_subject = TicketSubject::getByID($ticket->subject_id)->title;

                $all[] = [
                    'id' => $ticket->id,
                    'subject' => $title_subject,
                    'status' => $ticket->status,
                    'last_answer_at' => date('d.m.Y H:i', $ticket->last_answer_at),
                    'created_at' => date('d.m.Y H:i', $ticket->created_at),
                ];
            }

            if (count($tickets) > 0) {
                return response()->json(['ok' => true, 'result' => $all]);
            } else {
                throw new Exception('Нет тикетов');
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    public function list_subjects()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'tickets.info')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $type = $this->type;

            if($type == 0){


                $subjects = TicketSubject::where('status', 1)->orderByDesc('sort')->get();

            }    


            if($type == 1){ 
                 $subjects = TicketSubject::where('status', 1)->orderByDesc('sort')->get();
            }   

            $all = [];

            foreach ($subjects as $s) {

                $all[] = [
                    'id' => $s->id,
                    'title' => $s->title
                ];
            }

            return response()->json(['ok' => true, 'result' => $all]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function fullinfo($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'tickets.info')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $ticket = Ticket::find($id);

            if (!$ticket) {
                throw new Exception('Тикет не найден.', 1);
            }

            $member = Member::getByIDFromTicket($ticket->user_id);
            if ($member->username == ''){
                $block_user = $member->email;
            } else {
                $block_user = $member->username;
            }

            $messages = [];

            foreach (TicketMessage::getListByTicketID($id) as $msg) {

                $block_member = 'Support';

                if ($msg->user_id != 0){
                    $member = Member::getByIDFromTicket($msg->user_id);

                    if ($member->username == ''){
                        $block_member = $member->email;
                    } else {
                        $block_member = $member->username;
                    }
                }

                preg_match('/\b(?:https?|ftp):\/\/[^\s]+/', $msg->message, $matches);

                $msg_message = $msg->message;

                if (!empty($matches)) {
                    $url = $matches[0];
                    $msg_message = preg_replace('/' . preg_quote($url, '/') . '/', '<a target="_blank" href="'.$url.'">'.$url.'</a>', $msg->message);
                }
                $messages[] = [
                    'id' => $msg->id,
                    'user_id' => $msg->user_id,
                    'user' => $block_member,
                    'message' => $msg_message,
                    'created_at' => date('H:i', $msg->created_at)
                ];

            }

            $ticket->subject_title = TicketSubject::getByID($ticket->subject_id)->title;
            $ticket->user = $block_user;
            $ticket->messages = $messages;

            return response()->json([
                'ok' => true,
                'result' => $ticket,
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function messages($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'tickets.chat')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $ticket = Ticket::find($id);

            if (!$ticket) {
                throw new Exception('Тикет не найден.', 1);
            }

            $messages = [];

            foreach (TicketMessage::getListByTicketID($id) as $msg) {

                $block_member = 'Support';

                if ($msg->user_id != 0){
                    $member = Member::getByIDFromTicket($msg->user_id);

                    if ($member->username == ''){
                        $block_member = $member->email;
                    } else {
                        $block_member = $member->username;
                    }
                }

                preg_match('/\b(?:https?|ftp):\/\/[^\s]+/', $msg->message, $matches);

                $msg_message = $msg->message;

                if (!empty($matches)) {
                    $url = $matches[0];
                    $msg_message = preg_replace('/' . preg_quote($url, '/') . '/', '<a target="_blank" href="'.$url.'">'.$url.'</a>', $msg->message);
                }

                $msg->is_read = 1;
                $msg->save();

                $messages[] = [
                    'id' => $msg->id,
                    'user_id' => $msg->user_id,
                    'user' => $block_member,
                    'message' => $msg_message,
                    'image' => $msg->image,
                    'created_at' => date('H:i', $msg->created_at)
                ];
            }

            return response()->json([
                'ok' => true,
                'count' => count($messages),
                'result' => $messages,
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function last_messages($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'tickets.chat')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $ticket = Ticket::find($id);

            if (!$ticket) {
                throw new Exception('Тикет не найден.', 1);
            }

            $messages = [];

            foreach (TicketMessage::getListByCreatedAt($ticket->id) as $msg) {

                if($msg->operator_id > 0) {
                    $operator = Member::getDefaultByID($msg->operator_id);
                    $block_member = $operator->name;
                }


                if ($msg->user_id != 0){
                    $member = Member::getDefaultByID($msg->user_id);
                    $block_member = $member->username;
                }


                preg_match('/\b(?:https?|ftp):\/\/[^\s]+/', $msg->message, $matches);

                $msg_message = $msg->message;

                if (!empty($matches)) {
                    $url = $matches[0];
                    $msg_message = preg_replace('/' . preg_quote($url, '/') . '/', '<a target="_blank" href="'.$url.'">'.$url.'</a>', $msg->message);
                }

                $msg->is_read = 1;
                $msg->save();

                $messages[] = [
                    'id' => $msg->id,
                    'user_id' => $msg->user_id,
                    'user' => $block_member,
                    'message' => $msg_message,
                    'image' => $msg->image,
                    'created_at' => date('H:i', $msg->created_at)
                ];
            }

            return response()->json([
                'ok' => true,
                'count' => count($messages),
                'result' => $messages,
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }

    public function info($id)
    {
        try {
            $ticket = Ticket::getByIDUserID($id, $this->user->id);
            if(!$ticket) {throw new Exception('Тикет не найден!', 1);}

            $title_subject = TicketSubject::getByID($ticket->subject_id)->title;

            $messages = [];

            foreach (TicketMessage::getListByTicketID($id) as $msg) {
                $messages[] = [
                    'id' => $msg->id,
                    'user_id' => $msg->user_id,
                    'message' => $msg->message,
                    'image' => $msg->image,
                    'created_at' => date('H:i', $msg->created_at)
                ];
            }

            $result = [
                'subject' => $title_subject,
                'status' => $ticket->status,
                'last_answer_at' => date('d.m.Y H:i', $ticket->last_answer_at),
                'messages' => $messages
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }


    public static function autoCloseCron(){
        try {

            $shop = Shop::getDefault();
            $shop_token = Crypt::decryptString($shop->token);
            $tg = new Api($shop_token);

            $expired_at = strtotime(date('Y-m-d H:i'));

            $results = Ticket::where('expired_at', $expired_at)->where('status', '!=', 1)->get();

            foreach($results as $ticket){

                echo $ticket->expired_at;

                $member = Member::getByIDFromTicket($ticket->user_id);

                $ticket->status = 1;
                $ticket->save();

                if ($member->tid != 0){
                    $subject_title = TicketSubject::getByID($ticket->subject_id)->title;

                    $ki[] = [["text" => 'Мои обращения', "callback_data" => "tickets/all"]];

                    $kp = json_encode(["inline_keyboard" => $ki]);
                    $tg->sendMessage(['chat_id' => $member->tid, 'text' => "✅ <b>Обращение закрыто</b>\r\n\r\nТема: <b>".$subject_title."</b>", 'parse_mode' => 'HTML', 'reply_markup' => $kp]);
                }
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }


    public function close($id)
    {
        try {

            $shop = Shop::getDefault();
            $shop_token = Crypt::decryptString($shop->token);

            $access = RolePermission::getByPermission($this->user->role_id, 'tickets.chat')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $ticket = Ticket::find($id);
            if (!$ticket) {
                throw new Exception('Тикет не найден.', 1);
            }

            $count_messages = TicketMessage::where('ticket_id', $ticket->id)->count();

            if($count_messages == 1){
                throw new Exception('Чтобы закрыть тикет, необходимо ответить на него.', 1);
            }

            $member = Member::getByIDFromTicket($ticket->user_id);

            $ticket->operator_id = $this->user->id;
            $ticket->status = 1;
            $ticket->save();

            
            
            if ($member->tid != 0){
                $subject_title = TicketSubject::getByID($ticket->subject_id)->title;

                try {

                    $tg = new Api($shop_token);

                    $ki[] = [["text" => 'Мои обращения', "callback_data" => "tickets/all"]];

                    $kp = json_encode(["inline_keyboard" => $ki]);
                    $tg->sendMessage(['chat_id' => $member->tid, 'text' => "✅ <b>Обращение закрыто</b>\r\n\r\nТема: <b>".$subject_title."</b>", 'parse_mode' => 'HTML', 'reply_markup' => $kp]);

                } catch (Exception $e){
                    return response()->json([
                        'ok' => true,
                        'description' => 'Обращение закрыто',
                    ]);
                }   
            }

            return response()->json([
                'ok' => true,
                'description' => 'Обращение закрыто',
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function create(Request $request){
        try {

            $block_key = 'blocked_user_' . $this->user->id;
            if (Cache::has($block_key) && Cache::get($block_key)) {
                throw new Exception('Доступ к тикетам закрыт на 24 часа.', 1);
            }

            $validator = Validator::make($request->all(), [
                'subject_id' => 'required|int|min:1',
                'message' => 'required|string|min:4|max:1024',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            $tg = new Api(Crypt::decryptString($shop->token));

            $check_subject = TicketSubject::getByID($request->subject_id);
            if(!$check_subject){throw new Exception('Тема не найдена!', 1);}

            $date_now = Carbon::now()->timestamp;
            $expired_at = strtotime(date('Y-m-d H:i', strtotime($this->ticket_auto_close_date)));

            $member = Member::getByIDFromTicket($this->user->id);

            $sql = [
                'user_id' => $this->user->id,
                'operator_id' => 0,
                'subject_id' => $request->subject_id,
                'status' => 0,
                'last_answer_at' => $date_now,
                'expired_at' => $expired_at,
                'created_at' => $date_now,
            ];

            $insert_id = Ticket::insertGetId($sql);

            if ($insert_id) {

                $ticket = Ticket::getByID($insert_id);
                $ticket_subject = TicketSubject::getByID($ticket->subject_id);

                $admins = Member::getByRole();
                foreach ($admins as $a) {
                    try {
                        $role = RolePermission::getByPermission($a->role_id, 'tickets.all');
                        if ($role) {
                            if ($a->tid > 0) {
                                $ki_ticket[$a->id][] = ["text" => 'Ответить на тикет', "url" => config('app.url') . "/admin/tickets"];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki_ticket[$a->id], 1)]);
                                $tg->sendMessage(['chat_id' => $a->tid, 'text' => "🎟 Новый тикет\r\n├ Тема: <b>".$ticket_subject->title."</b>\r\n├ Источник: <b>Сайт</b>\r\n└ Пользователь: <b>".$member->username."</b>", 'parse_mode' => 'HTML', 'reply_markup' => $kp]);
                            }
                        }

                    } catch (Exception $e) {
                        continue;
                    }
                }

                $msg_id = TicketMessage::insertGetId([
                    'user_id' => 0,
                    'operator_id' => $this->user->id,
                    'ticket_id' => $insert_id,
                    'message' => e($request->message),
                    'image' => '',
                    'is_read' => 0,
                    'created_at' => $date_now,
                ]);

                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $insert_id
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    public function messages_create(Request $request){
        try {

            $block_key = 'blocked_user_' . $this->user->id;
            if (Cache::has($block_key) && Cache::get($block_key)) {
                throw new Exception('Доступ к тикетам закрыт на 24 часа.', 1);
            }

            $validator = Validator::make($request->all(), [
                'ticket_id' => 'required|int|min:1',
                'message' => 'required|string|min:4|max:1024',
                'image' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            $shop_token = Crypt::decryptString($shop->token);

            $image = '';

            if(!$request->filled($request->image)){
                $image = $request->image;
            }

            $ticket = Ticket::getByID($request->ticket_id);
            if (!$ticket){
                throw new Exception('Тикет не найден!', 1);
            }

            if ($ticket->status == 1){
                throw new Exception('Тикет закрыт!', 1);
            }

            $ticket->operator_id = $this->user->id;
            $ticket->save();

            $member = Member::getByIDFromTicket($ticket->user_id);

            $date_now = Carbon::now()->timestamp;

            $user_id = $this->user->id;
            $status = 0;

            if ($this->user->role_id >= 1){
                $user_id = 0;
                $status = 2;
            }

            $sql = [
                'user_id' => $user_id,
                'operator_id' => $this->user->id,
                'ticket_id' => $request->ticket_id,
                'message' => e($request->message),
                'image' => $image,
                'is_read' => 0,
                'created_at' => $date_now,
            ];


            $expired_at = strtotime(date('Y-m-d H:i', strtotime($this->ticket_auto_close_date)));
            Ticket::where('id', $request->ticket_id)->update(['last_answer_at' => $date_now, 'expired_at' => $expired_at, 'status' => $status]);

            $insert_id = TicketMessage::insertGetId($sql);

            if ($insert_id) {

                $tg = new Api($shop_token);

                if ($this->user->role_id == 0){
                    $ticket = Ticket::getByID($request->ticket_id);
                    $ticket_subject = TicketSubject::getByID($ticket->subject_id);

                    $admins = Member::getByRole();
                    foreach ($admins as $a) {
                        $role = RolePermission::getByPermission($a->role_id, 'tickets.all');
                        if ($role) {
                            if ($a->tid > 0) {
                                $ki_ticket[$a->id][] = ["text" => 'Ответить на тикет', "url" => config('app.url') . "/admin/tickets"];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki_ticket[$a->id], 1)]);
                                $tg->sendMessage(['chat_id' => $a->tid, 'text' => "🎟 Новый ответ в тикете\r\n├ Тема: <b>".$ticket_subject->title."</b>\r\n├ Источник: <b>Сайт</b>\r\n└ Пользователь: <b>".$member->username."</b>", 'parse_mode' => 'HTML', 'reply_markup' => $kp]);
                            }
                        }
                    }
                }

                if ($member->tid != 0){
                    $subject_title = TicketSubject::getByID($ticket->subject_id)->title;

                    $ki[] = ["text" => 'Ответить', "callback_data" => "tickets/reply/".$request->ticket_id];
                    $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                    $block_image = '';
                    if($image != ''){
                        $block_image = '<a href="'.config('app.url').'/i'.$image.'">🌇</a> ';
                    }

                    $tg->sendMessage(['chat_id' => $member->tid, 'text' => "✅ <b>Есть ответ на обращение</b>\r\n\r\nТема: <b>".$subject_title."</b>\r\n\r\nОтвет: ".$block_image."<code>".e($request->message)."</code>", 'parse_mode' => 'HTML', 'reply_markup' => $kp]);
                }

                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $insert_id
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], 200);
        }
    }

}
