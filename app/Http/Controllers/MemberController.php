<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Order;
use App\Models\Refil;
use App\Models\RolePermission;
use App\Models\Tariff;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketSubject;
use App\Models\Withdrawal;
use Exception;
use App\Models\Attach;
use App\Models\Member;
use App\Models\Shop;
use App\Models\ShopSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Yajra\Datatables\Facades\Datatables;

class MemberController extends Controller
{
    public $user;

    public $set_shop;
    public $main_currency_code;
    public $main_currency_symbol;
    public $member_currency_symbol;
    public $member_currency_code;

    public $shop_currency = ['RUB' => '₽', 'USD' => '$'];

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                $this->set_shop = ShopSettings::getDefault();

                $this->member_currency_symbol = $this->shop_currency[$this->user->currency] ?? null;
                $this->member_currency_code = $this->user->currency;

                $this->main_currency_symbol = $this->shop_currency[$this->set_shop->currency] ?? null;
                $this->main_currency_code = $this->set_shop->currency;
                return $next($request);
            });

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
        }
    }
    public function change_email(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'regex:/^[a-zA-Z0-9_.@]+$/', 'unique:users', 'min:3', 'max:40', 'email'],
            ]);

            $validator->setAttributeNames([
                'email' => 'Email',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message);
                }
            }

            $member = Member::getByID($this->user->id, $this->user->sid);
            $member->email = $request->email;
            $member->save();

            return response()->json([
                'ok' => true,
                'description' => 'Изменения сохранены'
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    public function change_login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users', 'min:3', 'max:40'],
            ]);

            $validator->setAttributeNames([
                'username' => 'логин',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message);
                }
            }

            $member = Member::getByID($this->user->id, $this->user->sid);
            $member->username = $request->username;
            $member->save();

            return response()->json([
                'ok' => true,
                'description' => 'Изменения сохранены'
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    public function change_password(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string|min:5|max:100',
                'new_password' => 'required|string|min:5|max:100',
                'repeat_password' => 'required|string|min:5|max:100',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message);
                }
            }

            if ($request->new_password != $request->repeat_password){
                throw new Exception('Повторный пароль введен неверно!');
            }

            $member = Member::getByID($this->user->id, $this->user->sid);
            if (Hash::check($request->old_password, $member->password)) {
                $member->password = Hash::make($request->new_password);
                $member->save();
                return response()->json([ 'ok' => true, 'description' => 'Изменения сохранены']);
            } else {
               throw new Exception('Старый пароль введен неверно!');
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    public function change_notify_tickets()
    {
        try {

            $member = Member::getByID($this->user->id, $this->user->sid);

            if($member->email_notify_tickets == 1){$status = 0;} else {$status = 1;}

            $member->email_notify_tickets = $status;
            $member->save();

            return response()->json([
                'ok' => true,
                'description' => 'Изменения сохранены'
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    public function change_notify_orders()
    {
        try {

            $member = Member::getByID($this->user->id, $this->user->sid);

            if($member->email_notify_orders == 1){$status = 0;} else {$status = 1;}

            $member->email_notify_orders = $status;
            $member->save();

            return response()->json([
                'ok' => true,
                'description' => 'Изменения сохранены'
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    public function change_notify_status_changed()
    {
        try {

            $member = Member::getByID($this->user->id, $this->user->sid);

            if($member->email_notify_status_changed == 1){$status = 0;} else {$status = 1;}

            $member->email_notify_status_changed = $status;
            $member->save();

            return response()->json([
                'ok' => true,
                'description' => 'Изменения сохранены'
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function unlink_telegram()
    {
        try {

            $member = Member::getByID($this->user->id, $this->user->sid);
    
            $member->tid = 0;
            $member->save();

            return response()->json([
                'ok' => true,
                'description' => 'Telegram-аккаунт отвязан'
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function formatDateTimeZone($date,$to)
    {

        $dateUTC = Carbon::now();
        $datePST = Carbon::now();
        $datePST->setTimezone($to);

        $start  = new Carbon($dateUTC);
        $end    = new Carbon($datePST);

        $seconds = strtotime($end) - strtotime($start);
        $hours = $seconds / 60 /  60;

        $carbon = Carbon::createFromDate(date('Y-m-d H:i:s', $date), $to);
        $carbon->addHours($hours);

        return $carbon->format('d.m.Y в H:i:s');
    }

    public function info($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'members.info')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $tg = new Api(Crypt::decryptString($shop->token));

            $member = Member::where('sid', $shop->id)->where('id', $id)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            return response()->json([
                'ok' => true,
                'result' => [
                    'id' => $member->tid,
                    'first_name' => '',
                    'username' => $member->username
                ]
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function fullinfo($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'members.info')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $tg = new Api(Crypt::decryptString($shop->token));

            $member = Member::where('sid', $shop->id)->where('id', $id)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            $ref_users = Member::where('rid', $member->id)->count();

            return response()->json([
                'ok' => true,
                'result' => [
                    'id' => $member->tid,
                    'first_name' => $member->username,
                    'username' => $member->username,
                    'balance_main' => number_format($member->balance_main, 2, '.', ' ').$this->main_currency_symbol,
                    'ref_percent' => $member->ref_percent,
                    'ref_users' => $ref_users,
                    'is_ban' => $member->is_ban,
                    'role_id' => $member->role_id,
                    'email_notify_tickets' => $member->email_notify_tickets,
                    'email_notify_orders' => $member->email_notify_orders,
                    'email_notify_status_changed' => $member->email_notify_status_changed,
                ]
            ]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

//    public function write(Request $request){
//        try {
//
//            $validator = Validator::make($request->all(), [
//                'id' => 'required|int|min:5',
//                'text' => 'required|string|min:4',
//            ]);
//
//            if($request->filled($request->image) && mb_strlen(strip_tags($request->text)) > config('app.tg_limit_text_no_image')){
//                throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_no_image').' символов.');
//            }
//
//            if(!$request->filled($request->image) && mb_strlen(strip_tags($request->text)) > config('app.tg_limit_text_image')){
//                throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_image').' символов.');
//            }
//
//            if ($validator->fails()) {
//                foreach ($validator->errors()->all() as $message) {
//                    throw new Exception($message, 1);
//                }
//            }
//
//            if(!$request->filled($request->image)){
//                $image_db = substr($request->image, 1);
//                $r = Attach::where('id', $image_db)->first();
//                if(!$r){throw new Exception('Изображение не найдено.', 1);}
//            }
//
//            $shop = Shop::getDefault();
//            if(!$shop){throw new Exception('Shop not found!');}
//
//            $tg = new Api(Crypt::decryptString($shop->token));
//
//            $member = Member::where('sid', $shop->id)->where('id', $request->id)->first();
//            if (!$member) {throw new Exception('Пользователь не найден.', 1);}
//
//            $tmember = $tg->getChat(['chat_id' => $member->tid]);
//
//            if($tmember){
//                $text = strip_tags($request->text, '<b><i><u><s><span><a><code><pre><del><em><ins>');
//                if(!$request->filled($request->image)){
//                    $tg->sendPhoto(['chat_id' => $member->tid, 'photo' => new InputFile(Attach::getPathById($image_db)), 'caption' => $text, 'parse_mode' => 'HTML']);
//                } else {
//                    $tg->sendMessage(['chat_id' => $member->tid, 'text' => $text, 'parse_mode' => 'HTML']);
//                }
//            }
//
//            return response()->json(['ok' => true, 'description' => 'Сообщение отправлено']);
//
//        } catch (Exception $e){
//            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
//        }
//    }

    public function orders(){
        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $currency_code = $this->user->currency;
            $currency_symbol = $this->shop_currency[$currency_code];

            $lists = [];

            $items = Order::where('bid', $this->user->id)
                ->where('status', 2)
                ->where('pid', '!=', 0)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($items as $item) {

//                var_dump($item->pid);


                $tariff = Tariff::getByID($shop->id, $item->pid, $item->tid);

                $block_tariff = 'Неизвестно';
                if(isset($tariff)) {
                    $block_tariff = Tariff::num_decline($tariff->title, ['день', 'дня', 'дней']);
                }

                $lists[] = [
                    'id' => $item->hash,
                    'title' => $item->title,
                    'tariff' => $block_tariff,
                    'amount' => Currency::convert($this->main_currency_code, $currency_code, $item->amount).$currency_symbol,
                    'status' => $item->status,
                    'delivery_hash' => $item->delivery_hash,
                    'payment_at' => date('d.m.Y H:i', $item->payment_at)
                ];
            }

            if (count($items) > 0) {
                return response()->json(['ok' => true, 'result' => $lists]);
            } else {
                throw new Exception('Нет заказов');
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage(), 'line' => $e->getLine()], 200);
        }
    }
    public function refills(){
        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $items = Refil::where('sid', $shop->id)
                ->where('user_id', $this->user->id)
                ->orderBy('id', 'desc')
                ->get();

            $result = [];

            foreach ($items as $item) {

                $m = Member::getByID($item->owner_id, $item->sid);

                $member_block = $m->username;
                $sum = Currency::convert($this->main_currency_code, $this->member_currency_code, $item->sum).$this->member_currency_symbol;

                $result[] = [
                    'id' => $item->id,
                    'member' => $member_block,
                    'sum' => $sum,
                    'created_at' => date('d.m.Y в H:i', $item->created_at),
                ];
            }
            if (count($items) > 0) {
                return response()->json(['ok' => true, 'result' => $result]);
            } else {
                throw new Exception('Нет пополнений');
            }
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function withdraw(){

        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $withdraws = [];

            $items = Withdrawal::where('sid', $shop->id)
                ->where('user_id', $this->user->id)
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get();

            foreach ($items as $item) {

                $withdraws[] = [
                    'id' => $item->id,
                    'sum' => Currency::convert($this->main_currency_code, $this->member_currency_code, $item->sum).$this->member_currency_symbol,
                    'method' => $item->method,
                    'status' => $item->status,
                    'created_at' => date('d.m.Y', $item->created_at)
                ];
            }

            if (count($items) > 0) {
                return response()->json(['ok' => true, 'result' => $withdraws]);
            } else {
                throw new Exception('Нет заявок на вывод');
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
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
            $role_id = $request->input('role_id');

            if($column_name == 'member'){$column_name = 'tid';}

            $access = RolePermission::getByPermission($this->user->role_id, 'members.all')->allow;
            if (!$access) {
                throw new Exception('Access Denied');
            }

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Shop not found!');
            }

            $tg = new Api(Crypt::decryptString($shop->token));

            if ($column_name && $order_dir) {
                $members = Member::where('sid', $shop->id)->orderBy($column_name, $order_dir);
            } else {
                $members = Member::where('sid', $shop->id)->orderBy('created_at', 'DESC');
            }

            if($role_id != null){
                $members = $members->where('role_id', $role_id);
            }

            if ($search_term) {
                $members = $members
                    ->where('username', 'LIKE', '%' . $search_term . '%')
                    ->orWhere('tid', 'LIKE', '%' . $search_term . '%');
            }

            $filtered_count = $members->count();
            $members = $members->offset($start)->limit($length)->get();
            $total_count = Member::getCountTableAll();

            $all = [];

            foreach ($members as $m) {

                if ($m->username != '') {
                    $block_member = $m->username;
                } else {
                    $block_member = $m->id;
                }

                $count_refs = Member::getCountReferralsByUserID($m->id);

                $block_percent = $this->set_shop->ref_percent;
                if ($m->ref_percent > 0) {
                    $block_percent = $m->ref_percent;
                }

                $block_ban = '<a href="javascript:;" onclick="ban(' . $m->id  . ');return false;" title="Заблокировать"><i class="far fa-lock-alt fa-xl text-warning"></i></a>';
                if ($m->is_ban == 1) {
                    $block_ban = '<a href="javascript:;" onclick="ban(' . $m->id  . ');return false;" title="Разблокировать"><i class="far fa-lock-open-alt fa-xl text-warning"></i></a>';
                }

                $all[] = [
                    'icon' => '<i class="far fa-user"></i>',
                    'id' => $m->id,
                    'member' => $block_member,
                    'count_ref' => $count_refs,
                    'balance_main' => number_format($m->balance_main, 2, '.', ' ') . $this->main_currency_symbol,
                    'ref_percent' => $block_percent,
                    'is_ban' => $m->is_ban,
                    'created_at' => date('d.m.Y в H:i', $m->created_at),
                    'block_edit' => '<a data-id="' . $m->id . '" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#editMember"><i class="far fa-edit fa-xl"></i></a>',
                    'block_ban' => $block_ban,
                    'block_delete' => '<a onclick="remove(\'member\',' . $m->id . ');return false;" title="Удалить" class="text-danger" href="javascript:;"> <i class="far fa-trash fa-xl"></i></a>'
                ];
            }

            return response()->json(['data' => $all, 'recordsTotal' => $total_count, 'recordsFiltered' => $filtered_count]);
//
//            return datatables($all)
//                ->setTotalRecords($total_count)
//                ->setFilteredRecords($total_count)
////            ->addColumn('block_msg', function ($row) {return '<a href="javascript:;" data-id="'.$row['id'].'" data-toggle="modal" data-target="#sendMessage" title="Написать сообщение"><i class="far fa-envelope fa-xl"></i></a>';})
//                ->addColumn('block_send_product', function ($row) {
//                    return '<a href="javascript:;" data-toggle="modal" data-target="#sendProduct" title="Написать сообщение"><i class="far fa-share fa-xl"></i></a>';
//                })
//                ->addColumn('block_ban', function ($row) {
//                    $status = '<a href="javascript:;" onclick="ban(' . $row['id'] . ');return false;" title="Заблокировать"><i class="far fa-lock-alt fa-xl text-warning"></i></a>';
//                    if ($row['is_ban'] == 1) {
//                        $status = '<a href="javascript:;" onclick="ban(' . $row['id'] . ');return false;" title="Разблокировать"><i class="far fa-lock-open-alt fa-xl text-warning"></i></a>';
//                    }
//                    return $status;
//                })
//                ->addColumn('block_edit', function ($row) {
//                    return '<a data-id="' . $row['id'] . '" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#editMember"><i class="far fa-edit fa-xl"></i></a>';
//                })
//                ->addColumn('block_delete', function ($row) {
//                    return '<a onclick="remove(\'member\',' . $row['id'] . ');return false;" title="Удалить" class="text-danger" href="javascript:;"> <i class="far fa-trash fa-xl"></i></a>';
//                })
//                ->rawColumns(['icon', 'member', 'block_send_product', 'block_msg', 'block_edit', 'block_ban', 'block_delete'])
//                ->make(true);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function list()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'members.all')->allow;
            if (!$access) {
                throw new Exception('Access Denied');
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $members = Member::where('sid', $shop->id)->orderByDesc('created_at')->get();

            $all = [];

            foreach ($members as $m) {

                if ($m->username != '') {
                    $block_member = $m->username;
                } else {
                    $block_member = $m->id;
                }

                $count_refs = Member::where('sid', $shop->id)->where('mid', $m->id)->count();

                $block_percent = $this->set_shop->ref_percent;
                if($m->ref_percent > 0){
                    $block_percent = $m->ref_percent;
                }

                $all[] = [
                    'icon' => '<i class="far fa-user"></i>',
                    'id' => $m->id,
                    'member' => $block_member,
                    'count_ref' => $count_refs,
                    'balance_main' => number_format($m->balance_main, 2, '.', ' ').$this->currency_symbol,
                    'ref_percent' => $block_percent,
                    'is_ban' => $m->is_ban,
                    'created_at' => $m->created_at,
                ];
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }


    public function list_test()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'members.all')->allow;
            if (!$access) {
                throw new Exception('Access Denied');
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $members = Member::where('sid', $shop->id)->where('rid', '>', 100000)
                ->offset(0)
                ->limit(3000)
                ->get();

            $all = [];

            foreach ($members as $m) {

                $check = Member::getByTID($m->rid, $shop->id);

                if(isset($check)){
                    Member::edit($m->id, $shop->id, 'rid', 0);
                    echo $m->id.'<br>';
                }

            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function delete(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'members.delete')->allow;
            if (!$access) {
                throw new Exception('Access Denied');
            }

            if(!$request->filled('id')){throw new Exception('ID not found!');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $member = Member::where('sid', $shop->id)->where('id', $request->id)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            $delete = Member::where('id', $request->id)->delete();

            if($delete) {
                return response()->json(['ok' => true, 'description' => 'Пользователь удален']);
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function ban(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'members.block')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            if(!$request->filled('id')){
                throw new Exception('ID not found!');
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $member = Member::where('sid', $shop->id)->where('id', $request->id)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            if($member->is_ban == 0){$val = 1;$status = 'заблокирован';}else{$val = 0;$status = 'разблокирован';}

            $update = Member::where('id', $request->id)->update(['is_ban' => $val]);
            if($update) {
                return response()->json(['ok' => true, 'description' => 'Пользователь '.$status]);
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
    public function update_balance(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'members.edit')->allow;
            if (!$access) { throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'id' => 'required|int|min:5',
                'type' => 'required|int|between:0,1',
                'value' => 'required|numeric|between:0.1,99999.99',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }


            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $tg = new Api(Crypt::decryptString($shop->token));

            $member = Member::where('sid', $shop->id)->where('id', $request->id)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            $currency_code = $member->currency;
            $currency_symbol = $this->shop_currency[$currency_code];

            if($request->type == 0) {

                if($request->value > Currency::convert($this->main_currency_code, $currency_code, $member->balance_main)){
                    throw new Exception('Balance cannot be less than 0'.$currency_symbol, 1);
                }

                $update = Member::where('id', $request->id)->where('sid', $shop->id)->decrement('balance_main', $request->value);
            }

            if($request->type == 1) {
                $update = Member::where('id', $request->id)->where('sid', $shop->id)->increment('balance_main', $request->value);
            }

            if($update) {

                $member_updated = Member::where('sid', $shop->id)->where('id', $request->id)->first();

                if($member->tid) {
                    if($request->type == 0) {$msg_notify = '⚠️ Списание с основного баланса: <b>'.Currency::convert($this->main_currency_code, $currency_code, $request->value).$currency_symbol.'</b>';}
                    if($request->type == 1) {$msg_notify = '✅ Поступление на основной баланс: <b>'.Currency::convert($this->main_currency_code, $currency_code, $request->value).$currency_symbol.'</b>';}

                    $tg->sendMessage(['chat_id' => $member->tid, 'text' => $msg_notify, 'parse_mode' => 'HTML']);
                }
                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'balance_main' => number_format(Currency::convert($this->main_currency_code, $currency_code, $member_updated->balance_main), 2, '.', ' ').$currency_symbol,
                    ]
                ]);
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
    public function update_ref(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'members.edit')->allow;
            if (!$access) { throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'id' => 'required|int|min:5',
                'value' => 'required|int|min:1|max:100',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            $tg = new Api(Crypt::decryptString($shop->token));

            $member = Member::where('sid', $shop->id)->where('id', $request->id)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            $update = Member::where('id', $request->id)->where('sid', $shop->id)->update(['ref_percent' => $request->value]);
            if($update) {

                if($member->tid) {
                    $msg_notify = '✅ Изменен реферальный процент: <b>'.$request->value.'%</b>';
                    $tg->sendMessage(['chat_id' => $member->tid, 'text' => $msg_notify, 'parse_mode' => 'HTML']);
                }

                return response()->json(['ok' => true, 'description' => 'Сохранено']);
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
    public function update_role(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'members.edit')->allow;
            if (!$access) { throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'id' => 'required|int|min:5',
                'value' => 'required|int',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();

            $member = Member::where('sid', $shop->id)->where('id', $request->id)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            $update = Member::where('id', $request->id)->where('sid', $shop->id)->update(['role_id' => $request->value]);
            if($update) {
                return response()->json(['ok' => true, 'description' => 'Сохранено']);
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function import(Request $request){
        try {

            set_time_limit(0);

            $access = RolePermission::getByPermission($this->user->role_id, 'members.import')->allow;
            if (!$access) { throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'id' => 'required|string|min:4',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $attach = Attach::getById($request->id);
            if(!$attach){throw new Exception('Файл не найден.', 1);}

            $dir = str_replace('app/Http/Controllers', '', __dir__);

            if(!$request->filled($request->id)){
                $result = file($dir.'storage/app/public/files/'.$request->id.'.'.$attach->ext);
            }

            $count = 0;
            foreach (json_decode($result[0], true) as $item) {
                $created_at = strtotime($item['created_at']);
                $balance = Currency::convert('RUB', 'USD', $item['ref_balance']);
                echo Member::import_add($item['chat_id'], $item['ref_id'], $balance, $item['ref_percent'], $created_at);
                $count++;
            }

            return response()->json(['ok' => true, 'count' => $count, 'description' => 'Добавлено']);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function export($type){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'members.export')->allow;
            if (!$access) { throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $all = Member::where('sid', $shop->id)->get();

            if($type == 'json') {

                $members = [];

                foreach ($all as $a) {
                    $members[] = [
                        'id' => $a->id,
                        'balance_main' => $a->balance_main,
                        'balance_affiliate' => $a->balance_affiliate,
                        'ref_percent' => $a->ref_percent,
                        'is_ban' => $a->is_ban,
                        'joined_at' => $a->created_at
                    ];
                }

                $path = 'public/files/';
                $filename = Str::random(16);

                if(Storage::disk('local')->put($path.$filename.'.json', json_encode($members))){
                    return Storage::download($path.$filename.'.json');
                }
            }


        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
}
