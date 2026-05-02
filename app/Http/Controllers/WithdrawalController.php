<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Member;
use App\Models\RolePermission;
use App\Models\Shop;
use App\Models\ShopSettings;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Telegram\Bot\Api;

class WithdrawalController extends Controller
{
    public $user;
    private $set_shop;
    private $main_currency;
    private $currency_symbol;
    private $currency_code;
    private $def_currency_symbol;
    private $shop_currency;

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();

                $this->set_shop = ShopSettings::getDefault();
                $this->main_currency = $this->set_shop->currency;
                $this->shop_currency = ['RUB' => '₽', 'USD' => '$'];

                $this->def_currency_symbol = $this->shop_currency[$this->set_shop->currency];

                if ($this->user) {
                    $this->currency_symbol = $this->shop_currency[$this->user->currency];
                    $this->currency_code = $this->user->currency;
                } else {
                    $this->currency_symbol = $this->shop_currency[$this->set_shop->currency];
                    $this->currency_code = $this->set_shop->currency;
                }

                return $next($request);
            });

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
            ]);
        }
    }

    public function create(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'method_id' => 'required|int|min:1',
            ]);

            if ($request->input('method_id') != 3) {
                $validator->addRules([
                    'requisites' => 'required|string|min:5|max:50',
                    'is_confirm' => 'required|int|min:0|max:1',
                ]);
            }

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $balance_affiliate = Currency::convert($this->main_currency, $this->currency_code, $this->user->balance_affiliate);
            $min_sum_withdrawal_card = Currency::convert($this->main_currency, $this->currency_code, $this->set_shop->min_sum_withdrawal_card);
            $min_sum_withdrawal_balance = Currency::convert($this->main_currency, $this->currency_code, $this->set_shop->min_sum_withdrawal_balance);

            $check_withdraw = Withdrawal::check($this->user->id);
            if($check_withdraw){throw new Exception('Заявка на вывод средств уже отправлена.', 1);}

            $check_method = WithdrawalMethod::getByID($request->method_id);
            if(!$check_method){throw new Exception('Метод вывода не найден.', 1);}

            if($request->is_confirm == 0 && $request->method_id != 3){
                throw new Exception('Вы не подтвердили правильность введенных данных.', 1);
            }

            if($request->method_id != 3 && $balance_affiliate < $min_sum_withdrawal_card){
                throw new Exception('Для вывода средств баланс должен быть равен или больше '.$min_sum_withdrawal_card.$this->currency_symbol.'.', 1);
            }

            if($request->method_id == 3 && $balance_affiliate < $min_sum_withdrawal_balance){
                throw new Exception('Для вывода средств баланс должен быть равен или больше '.$min_sum_withdrawal_balance.$this->currency_symbol.'.', 1);
            }

            if($request->method_id == 3){
                Member::transferBalance($this->user->id, $this->user->sid);
            }

            $insert_id = Withdrawal::add($this->user->id, $this->user->sid, $this->user->balance_affiliate, $request->requisites, '', $request->method_id, 0);

            if ($insert_id) {

                Member::edit($this->user->id, $this->user->sid, 'balance_affiliate', 0);

                $shop = Shop::getByID($this->user->sid);
                $shop_token = Crypt::decryptString($shop->token);

                $tg = new Api($shop_token);
                $ki_adm[] = ["text" => "Подтвердить перевод", "callback_data" => "adm/withdrawal/accept/".$insert_id];

                $member_block = $this->user->username." (ID: <code>".$this->user->id."</code>)";

                $message_text = str_replace(':sum', $this->user->balance_affiliate.$this->def_currency_symbol, __('bot.text_adm_withdrawal'));
                $message_text = str_replace(':card_number', $request->requisites, $message_text);
                $message_text = str_replace(':member_block', $member_block, $message_text);

                $kp_adm = json_encode(["inline_keyboard" => array_chunk($ki_adm, 1)]);
                $tg->sendMessage(['chat_id' => $this->set_shop->notify_target_id, 'text' => $message_text, "reply_markup" => $kp_adm, "parse_mode" => "HTML"]);

                return response()->json(['ok' => true, 'description' => 'Отправлена заявка на вывод.', 'result' => ['id' => $insert_id]]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function confirm($id){
        try {
            $shop = Shop::getDefault();
            $shop_token = Crypt::decryptString($shop->token);

            $access = RolePermission::getByPermission($this->user->role_id, 'withdrawals.all')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $withdraw = Withdrawal::getByID($id);
            if(!$withdraw){throw new Exception('Заявка на вывод не найдена.', 1);}

            $method = WithdrawalMethod::getByID($withdraw->method);
            if(!$method){throw new Exception('Метод вывода не найден.', 1);}

            $member = Member::where('id', $withdraw->user_id)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            $currency_code = $member->currency;
            $member_currency_symbol = $this->shop_currency[$currency_code];
            $sum_currency = Currency::convert($this->main_currency, $currency_code, $withdraw->sum);

            if(Withdrawal::edit($id, $withdraw->user_id, 'status', 2)){
                Mail::send('emails.success-withdraw', ['sum' => $sum_currency.$member_currency_symbol, 'req' => $method->title.' ('.$withdraw->card_number.')'], function ($message) use ($member) {
                    $message->to($member->email, $member->username)
                        ->subject('Вывод с реферальной программы');
                });

                if ($member->tid != 0) {
                    $tg = new Api($shop_token);
                    $alert_text = str_replace(':sum', $sum_currency.''.$member_currency_symbol, __('bot.alert_withdrawal_accepted'));
                    $alert_text = str_replace(':card_number', $withdraw->card_number, $alert_text);
                    $tg->sendMessage(['chat_id' => $member->tid, 'text' => $alert_text, 'parse_mode' => 'HTML']);
                }

                return response()->json(['ok' => true, 'description' => 'Вывод подтвержден.']);
            }


        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function all()
    {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'withdrawals.all')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();

            $results = Withdrawal::where('sid', $shop->id)
                ->orderBy('id', 'desc')
                ->get();

            $all = [];

            foreach ($results as $w) {

                $member = Member::getByID($w->user_id, $w->sid);

                if ($w->status == 1){
                    $status = '<span class="badge bg-warning px-2" style="color: #000">Ожидает</span>';
                    $block_done = '<a data-id="'.$w->id.'" href="javascript:;" title="Подтвердить вывод" onclick="confirmWithdraw('.$w->id.');"><i class="far fa-check-circle fa-xl text-success"></i></a>';
                }
                if ($w->status == 2){
                    $status = '<span class="badge bg-success px-2" style="color: #000">Выведено</span>';
                    $block_done = '<a href="javascript:;" title="Подтвердить вывод"><i class="far fa-check-circle fa-xl text-secondary"></i></a>';
                }

                $method = WithdrawalMethod::getByID($w->method);

                $method_with = 'Неизвестно';
                if($method){
                    $method_with = $method->title;
                }

                $all[] = [
                    'id' => $w->id,
                    'icon' => '<i class="far fa-comments-dollar mr-1"></i>',
                    'member' => $member->username,
                    'sum' => $w->sum.$this->def_currency_symbol,
                    'method' => $method_with,
                    'card_number' => $w->card_number,
                    'status' => $status,
                    'created_at' => date('d.m.Y в H:i', $w->created_at),
                    'block_done' => $block_done,
                ];
            }

            return datatables($all)
                ->rawColumns(['icon', 'status', 'block_done'])
                ->make(true);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }


}
