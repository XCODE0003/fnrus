<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Currency;
use App\Models\HackStatus;
use App\Models\Member;
use App\Models\MethodPayment;
use App\Models\Order;
use App\Models\PaymentAsset;
use App\Models\PaymentSystem;
use App\Models\Refil;
use App\Models\RolePermission;
use App\Models\Shop;
use App\Models\ShopSettings;
use App\Models\Material;
use App\Models\Tariff;
use App\Models\Product;
use App\Models\TicketSubject;
use Exception;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Yajra\Datatables\Facades\Datatables;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public $user;
    private $set_shop;
    private $main_currency;
    private $currency_symbol;
    private $def_currency_symbol;
    private $shop_currency;
    private $notify_target_id;
    private $timezone;

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();

                $this->set_shop = ShopSettings::getDefault();
                $this->main_currency = $this->set_shop->currency;
                $this->shop_currency = ['RUB' => '₽', 'USD' => '$'];


                $this->notify_target_id = $this->set_shop->notify_target_id;

                $this->def_currency_symbol = $this->shop_currency[$this->set_shop->currency];

                if ($this->user) {
                    if($this->user->tz != '') {
                        $this->timezone = $this->user->tz;
                    } else {
                        $this->timezone = $this->set_shop->default_timezone;
                    }
                    $this->currency_symbol = $this->shop_currency[$this->user->currency];
                } else {
                    $this->timezone = $this->set_shop->default_timezone;
                    $this->currency_symbol = $this->shop_currency[$this->set_shop->currency];
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

    public function formatDateTimeZone($date,$to)
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

        return $carbon->format('d.m.Y в H:i:s');
    }

    public function create(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'sid' => 'required|int|min:1',
                'pid' => 'required|int|min:1',
                'bid' => 'required|int|min:1',
                'count' => 'required|int|min:1'
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $date_now = Carbon::now()->timestamp;

            $shop = Shop::where('id', $request->sid)->first();

            if(!$shop){
                throw new Exception('Shop not found!', 1);
            }

            $product = Product::where('id', $request->pid)->where('sid', $shop->id)->first();

            if(!$product){
                throw new Exception('Product not found!', 1);
            }

            $member = Member::where('id', $request->bid)->where('sid', $shop->id)->first();

            if(!$member){
                throw new Exception('Member not found!', 1);
            }

            if($request->count > $product->count_all && $product->is_endless == 0){
                throw new Exception('This item does not exist', 1);
            }

            $count_all = $request->count;

            if($product->is_endless == 1){$count_all = 1;}

            $order = DB::table('orders')
                ->where('sid', $request->sid)
                ->where('pid', $request->pid)
                ->where('bid', $request->bid)
                ->where('count_all', $count_all)
                ->where('expired_at', '>', $date_now)
                ->where('status', 1)
                ->first();

            if($order){
                return response()->json([
                    'ok' => true,
                    'result' => [
                        'id' => $order->id,
                        'amount' => $order->amount,
                        'currency' => $order->currency,
                        'count' => $order->count_all,
                        'expired_at' => $order->expired_at
                    ]
                ]);
            }


            $expired_at = strtotime(date('Y-m-d H:i', strtotime('+'.$this->set_shop->booking_time.' minutes')));
            $order_hash = mb_strtoupper(Str::random(config('app.order_hash_line')));
            $delivery_hash = Str::random(config('app.delivery_hash_line'));
            $amount = Currency::convert($this->main_currency,$this->user->currency,$product->price * $count_all);

            $hack_status = HackStatus::getByID($product->hack_status);

            $status = '';
            if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

            $sql = [
                'sid' => $request->sid,
                'pid' => $request->pid,
                'bid' => $request->bid,
                'mid' => 0,
                'oid' => '0',
                'title' => $product->title.' '.$status,
                'amount' => $amount,
                'currency' => $this->set_shop->currency,
                'amount_rub' => 0,
                'count_all' => $count_all,
                'status' => 1,
                'hash' => $order_hash,
                'delivery_hash' => $delivery_hash,
                'payment_at' => 0,
                'expired_at' => $expired_at,
                'created_at' => $date_now,
            ];

            $insert_id = Order::insertGetId($sql);

            if ($insert_id) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Order created!',
                    'result' => [
                        'id' => $insert_id,
                        'amount' => $amount,
                        'currency' => $this->set_shop->currency,
                        'count' => $count_all,
                        'expired_at' => $expired_at
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function info($hash){
        try {
//            $access = RolePermission::getByPermission($this->user->role_id, 'orders.info')->allow;
//            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $order = Order::where('sid', $shop->id)
                ->where('hash', $hash)
                ->where('status', 1)
                ->first();
            if (!$order) {
                throw new Exception('Заказ не найден.', 1);
            }

            $member = Member::where('sid', $shop->id)->where('id', $order->bid)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            $currency_code = $member->currency;
            $currency_symbol = $this->shop_currency[$currency_code];

            return response()->json([
                'ok' => true,
                'result' => [
                    'sum_main' => Currency::convert($this->main_currency, $currency_code, $order->amount).$currency_symbol,
                    'sum_usd' => $order->amount
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }
    public function get_payment_link(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'order_id' => 'required|int|min:1',
                'method_pay_id' => 'required|int|min:0',
            ]);

            $validator->setAttributeNames([
                'method_pay_id' => 'способ оплаты',
                'order_id' => 'номер заказа',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $date_now = Carbon::now()->timestamp;

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $tg = new Api(Crypt::decryptString($shop->token));

            $member = Member::where('sid', $shop->id)->where('id', $this->user->id)->first();
            if (!$member) {throw new Exception('Пользователь не найден.', 1);}

            $order = Order::where('sid', $shop->id)->where('id', $request->order_id)->first();
            if (!$order) {throw new Exception('Заказ не найден.', 1);}

            $product = Product::getByID($shop->id, $order->pid);

            if($order->pid > 0 && $order->expired_at < strtotime('NOW')){
                throw new Exception('Срок оплаты заказа истек.', 1);
            }

            if ($order->pid > 0 && $order->status == 2){
                return response()->json(['ok' => true, 'result' => ['material_link' => config('app.url_delivery') . $order->delivery_hash]]);
            } else if ($order->pid == 0 && $order->status == 2){
                return response()->json(['ok' => true]);
            }

            if ($order->status == 3){
                throw new Exception('Заказ был отменен.', 1);
            }

            if ($order->status == 4){
                throw new Exception('Истек срок оплаты заказа.', 1);
            }

            if ($request->method_pay_id < 99) {

                $payment_asset = PaymentAsset::where('id', $request->method_pay_id)->where('active', 1)->first();
                if (!$payment_asset) {
                    throw new Exception('Валюта не активна.', 1);
                }

                $payment_system = PaymentSystem::where('id', $payment_asset->psid)->where('active', 1)->first();
                if (!$payment_system) {
                    throw new Exception('Платежная система не активна.', 1);
                }

                // Per-asset min/max enforcement (mirrors InvoiceController::method).
                // Done here so the user gets an inline notification on the buy popup
                // instead of being redirected to /invoice/{hash}.
                $main_currency_code = isset($this->main_currency) ? $this->main_currency : (ShopSettings::getDefault()->currency ?? 'RUB');
                $amount_in_asset = Currency::convert($main_currency_code, $payment_asset->currency, $order->amount);
                if (!empty($amount_in_asset) && $amount_in_asset > 0) {
                    if ($payment_asset->min > 0 && $amount_in_asset < $payment_asset->min) {
                        $min_in_main = Currency::convert($payment_asset->currency, $main_currency_code, $payment_asset->min);
                        $min_fmt = !empty($min_in_main) ? rtrim(rtrim(number_format($min_in_main, 2, '.', ''), '0'), '.') . ' ' . $main_currency_code : rtrim(rtrim(number_format($payment_asset->min, 2, '.', ''), '0'), '.') . ' ' . $payment_asset->currency;
                        throw new Exception('Минимальная сумма оплаты для данного способа — ' . $min_fmt . '. Пожалуйста, увеличьте сумму заказа или выберите другой способ оплаты.', 1);
                    }
                    if ($payment_asset->max > 0 && $amount_in_asset > $payment_asset->max) {
                        $max_in_main = Currency::convert($payment_asset->currency, $main_currency_code, $payment_asset->max);
                        $max_fmt = !empty($max_in_main) ? rtrim(rtrim(number_format($max_in_main, 2, '.', ''), '0'), '.') . ' ' . $main_currency_code : rtrim(rtrim(number_format($payment_asset->max, 2, '.', ''), '0'), '.') . ' ' . $payment_asset->currency;
                        throw new Exception('Максимальная сумма оплаты для данного способа — ' . $max_fmt . '. Пожалуйста, уменьшите сумму заказа или выберите другой способ оплаты.', 1);
                    }
                }

                return response()->json(['ok' => true, 'result' => ['payment_link' => config('app.url_invoice') . $order->hash . '/' . $payment_system->type.'/'.$payment_asset->id]]);

            } else if ($request->method_pay_id == 99){

                $funds = Currency::convert($this->main_currency,$this->user->currency, $order->amount - $this->user->balance_main);

                if ($order->expired_at < $date_now){
                    $alert_text = __('bot.alert_payment_time_expired');
                    throw new Exception($alert_text, 1);
                 }

                if($order->amount > $this->user->balance_main){
                    $alert_text = str_replace(':sum', $funds.$this->currency_symbol, __('bot.alert_insufficient_funds'));
                    throw new Exception($alert_text, 1);
                }

                Product::where('id', $order->pid)->increment('count_sales', 1);

                Material::markSoldForOrder($order->pid, $order->tid, $order->id, max(1, (int)$order->count_all));

                Order::editByHash($order->hash, $shop->id, 'method_payment', 'bc');
                Order::editByHash($order->hash, $shop->id, 'status', 2);
                Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                Member::where('id', $this->user->id)->where('sid', $shop->id)->decrement('balance_main', $order->amount);
                // count_all already decremented at order creation (create_site/BotController)

                $source = 'Сайт';

                try {
                    $tg->sendMessage(['chat_id' => $this->notify_target_id, 'text' => "💸 Новая покупка\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Товар: <b>" . $product->title . "</b>\r\n├ Сумма: <b>" . $order->amount. $this->def_currency_symbol . "</b>\r\n├ Способ оплаты: <b>Баланс</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);
                } catch (\Exception $tgEx) {
                    \Log::warning('Telegram notify failed (balance payment): ' . $tgEx->getMessage());
                }

                if($member->rid > 0){
                    $ref_id = $member->rid;
                    $m_ref = Member::getByID($ref_id, $shop->id);
                    if(isset($m_ref)) {
                        $percent = $m_ref->ref_percent / 100;
                        $balance = $order->amount * $percent;
                        $ref_currency = $this->shop_currency[$m_ref->currency];
                        $sum_currency = Currency::convert($this->main_currency, $m_ref->currency, $balance);
                        $ref_balance = Currency::convert($this->main_currency, $m_ref->currency, $balance + $m_ref->balance_affiliate);

                        Member::where('id', $ref_id)->where('sid', $shop->id)->increment('balance_affiliate', $balance);
                        Refil::insertGetId(['sid' => $shop->id, 'owner_id' => $member->id, 'user_id' => $ref_id, 'sum' => $balance, 'created_at' => strtotime('NOW')]);

                        if ($m_ref->tid > 0) {
                            try {
                                $tg->sendMessage(['chat_id' => $m_ref->tid, 'text' => "🤑 Реферальное вознаграждение\r\n├ Ваш доход: <b>" . $sum_currency . $ref_currency . " (" . $m_ref->ref_percent . "%)</b>\r\n└ Доступно для вывода: <b>" . $ref_balance . $ref_currency . "</b>", "parse_mode" => "HTML"]);
                            } catch (\Exception $tgEx) {}
                        }
                    }
                }

                if($order->promo_id != 0) {
                    Coupon::where('id', $order->promo_id)->where('sid', $shop->id)->decrement('count_uses_max', 1);
                    CouponUse::create(['promo_id' => $order->promo_id, 'chat_id' => $this->user->id, 'shop_id' => $shop->id, 'created_at' => $date_now]);
                }

                $hack_status = HackStatus::getByID($product->hack_status);

                $status = '';
                if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                if($member->email_notify_orders == 1 && $member->email != '') {
                    try {
                        Mail::send('emails.order-receipt', ['product_title' => $product->title.' '.$status, 'product_sum' => Currency::convert($this->main_currency,$this->user->currency,$order->amount).$this->currency_symbol, 'order_link' => config('app.url_delivery').$order->delivery_hash], function ($message) use ($member) {
                            $message->to($member->email, $member->username)
                                ->subject('Новая покупка на сайте');
                        });
                    } catch (\Exception $mailEx) {
                        \Log::warning('Order email notification failed: ' . $mailEx->getMessage());
                    }
                }

                return response()->json(['ok' => true, 'result' => ['material_link' => config('app.url_delivery') . $order->delivery_hash]]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function check($hash)
    {
        try {
            $shop = Shop::getDefault();

            $order = Order::where('sid', $shop->id)->where('bid', $this->user->id)->where('hash', $hash)->first();
            if (!$order) {throw new Exception('Заказ не найден.', 1);}

            if($order->pid > 0 && $order->expired_at < strtotime('NOW')){
                throw new Exception('Срок оплаты заказа истек.', 1);
            }

            if ($order->pid > 0 && $order->status == 1){
                $date_now = Carbon::now()->timestamp;
                $expired_sec = $order->expired_at - $date_now;

                $minutes = floor($expired_sec / 60);
                $remainingSeconds = $expired_sec % 60;

                $expired_sec = sprintf('%02d:%02d', $minutes, $remainingSeconds);

                return response()->json(['ok' => true, 'action' => 'wait', 'expired_sec' => $expired_sec]);
            } else if ($order->pid == 0 && $order->status == 1){
                return response()->json(['ok' => true, 'action' => 'wait']);
            }

            if ($order->pid > 0 && $order->status == 2){
                return response()->json(['ok' => true, 'action' => 'done', 'result' => ['material_link' => config('app.url_delivery') . $order->delivery_hash]]);
            } else if ($order->pid == 0 && $order->status == 2){
                return response()->json(['ok' => true, 'action' => 'done']);
            }

            if ($order->status == 3){
                throw new Exception('Заказ был отменен.', 1);
            }

            if ($order->status == 4){
                throw new Exception('Истек срок оплаты заказа.', 1);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    public function create_site(Request $request){
        try {

            $rules = [
                'product_id' => 'required|int|min:1',
                'tariff_id' => 'required|int|min:1',
                'is_applied' => 'required|int|min:0|max:1',
                'promocode' => 'nullable|string',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $count_all = 1;
            $promo_id = 0;


            if (!Auth::check()){
                throw new Exception('Для продолжения необходимо войти или зарегистрироваться.');
            }

            if (Auth::check()) {
                $joined_at = $this->user->created_at;
                $date_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $date_now = Carbon::now()->timestamp;

            if (Auth::check()) {
                $member = Member::where('id', $this->user->id)->where('sid', $this->user->sid)->first();
                if (!$member) {
                    throw new Exception('Пользователь не найден.', 1);
                }
            }

            $product = Product::where('id', $request->product_id)->where('sid', $shop->id)->first();
            if(!$product){throw new Exception('Товар не найден.', 1);}

            $tariff = Tariff::getByID($shop->id, $product->id, $request->tariff_id);
            if(!$tariff){throw new Exception('Тариф не найден.');}

            $count_materials = Material::getCountAll($tariff->id, $product->id);
            if(!$count_materials){throw new Exception('Товары в данном тарифе закончились.');}

            $amount = $tariff->price;
            $amount_main = $tariff->price;

            if (!empty($request->input('promocode')) and $request->is_applied == 1) {

                $promo = Coupon::getByCode($shop->id, $product->id, $request->promocode);
                if (!$promo) {throw new Exception('Промокод не найден.');}

                $promo_min_sum = $promo->min_sum;
                $sum = $tariff->price;

                if($promo->is_new_users == 1 && $date_ago > $joined_at && $this->user){
                   throw new Exception('Этот промокод действует только для новых пользователей.');
                }

                if($promo->is_new_users == 1 && !$this->user){
                    throw new Exception('Этот промокод действует только для зарегистрированных пользователей.');
                }

                $count_uses_user = CouponUse::where('promo_id', $promo->id)
                    ->where('chat_id', $this->user->id)
                    ->where('shop_id', $shop->id)
                    ->count();

                if($promo->is_one_time == 1 && $count_uses_user >= 1){
                    throw new Exception('Вы уже использовали данный промокод');
                }

                if($promo->count_uses_max == 0){
                    throw new Exception('Число активаций промокода исчерпано.');
                }

                if($promo_min_sum > $sum){
                    throw new Exception('Минимальная сумма для использования промокода '.$promo_min_sum.'.');
                }

                if($promo->sale_type == 0) {
                    if($promo->sale > 100){
                        throw new Exception('Cкидка промокода не может превышать более 100%.');
                    }
                    $amount = number_format($sum - ($sum * $promo->sale / 100), 2, '.', '');
                    $amount_main = number_format($sum - ($sum * $promo->sale / 100),  2, '.', '');

                }
                if($promo->sale_type == 1) {
                    if($promo->sale > $sum){
                        throw new Exception('Скидка промокода не может превышать сумму заказа.');
                    }
                    $amount = number_format($sum - $promo->sale, 2, '.', '');
                    $amount_main = number_format($sum - $promo->sale, 2, '.', '');
                }

                $promo_id = $promo->id;

            }

//            echo $amount;

            $order = Order::where('pid', $request->product_id)
                ->where('tid', $request->tariff_id)
                ->where('bid', $this->user->id)
//                ->where('amount', $amount)
                ->where('promo_id', $promo_id)
                ->where('count_all', $count_all)
                ->where('expired_at', '>', $date_now)
                ->where('status', 1)
                ->first();

            $amount_full = Currency::convert($this->main_currency, $this->user->currency, $amount_main);

            if($order){
                $expired_sec = $order->expired_at - $date_now;
                return response()->json([
                    'ok' => true,
                    'result' => [
                        'id' => $order->id,
                        'hash' => $order->hash,
                        'amount' => $amount_full.$this->currency_symbol,
                        'amount_full' => $amount_full,
                        'currency' => $order->currency,
                        'count' => $order->count_all,
                        'time_expired' => Tariff::num_decline($tariff->title, ['день','дня','дней'] ),
                        'expired_at' => $order->expired_at,
                        'expired_sec' => $expired_sec
                    ]
                ]);
            }

            $expired_at = strtotime(date('Y-m-d H:i', strtotime('+'.$this->set_shop->booking_time.' minutes')));
            $order_hash = mb_strtoupper(Str::random(config('app.order_hash_line')));
            $delivery_hash = Str::random(config('app.delivery_hash_line'));
            $expired_sec = $expired_at - $date_now;

            $hack_status = HackStatus::getByID($product->hack_status);

            $status = '';
            if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

            $sql = [
                'sid' => $shop->id,
                'pid' => $request->product_id,
                'bid' => $this->user->id,
                'tid' => $request->tariff_id,
                'oid' => '0',
                'title' => $product->title.' '.$status,
                'amount' => $amount,
                'amount_rub' => 0,
                'currency' => $this->set_shop->currency,
                'count_all' => $count_all,
                'promo_id' => $promo_id,
                'is_sent' => 0,
                'msg_id' => 0,
                'method_payment' => '',
                'status' => 1,
                'type' => 1,
                'hash' => $order_hash,
                'delivery_hash' => $delivery_hash,
                'payment_at' => 0,
                'expired_at' => $expired_at,
                'created_at' => $date_now,
            ];

            $insert_id = Order::insertGetId($sql);

            if ($insert_id) {
                Product::where('id', $request->product_id)->decrement('count_all', $count_all);
                Material::reserveForOrder($request->product_id, $request->tariff_id, $insert_id, $this->user->id, $count_all);

                return response()->json([
                    'ok' => true,
                    'description' => 'Заказ создан.',
                    'result' => [
                        'id' => $insert_id,
                        'hash' => $order_hash,
                        'amount' => Currency::convert($this->main_currency, $this->user->currency, $amount_main).$this->currency_symbol,
                        'amount_full' => Currency::convert($this->main_currency, $this->user->currency, $amount_main),
                        'currency' => $this->set_shop->currency,
                        'count' => $count_all,
                        'time_expired' => Tariff::num_decline($tariff->title, ['день','дня','дней'] ),
                        'expired_at' => $expired_at,
                        'expired_sec' => $expired_sec
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage(), 'line' => $e->getLine()], 200);
        }
    }

    public function cancel($hash){
        try {
            $order = Order::where('hash', $hash)->first();
            if(!$order){
                throw new Exception('Заказ не найден.', 1);
            }
            if($order->status != 1){
                throw new Exception('Отменить можно только неоплаченный заказ.', 1);
            }
            $shop = Shop::where('id', $order->sid)->first();
            Order::editByHash($hash, $shop->id, 'status', 3);
            if($order->pid > 0){
                Product::where('id', $order->pid)->increment('count_all', $order->count_all);
            }
            Material::releaseFromOrder($order->id);
            return response()->json(['ok' => true, 'description' => 'Заказ отменён']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
//        try {
//
//            $validator = Validator::make($request->all(), [
//                'method_id' => 'required|int|min:0',
//                'sum' => 'required|numeric'
//            ]);
//
//            $validator->setAttributeNames([
//                'method_id' => 'способ оплаты',
//                'sum' => 'сумма',
//            ]);
//
//            if ($validator->fails()) {
//                foreach ($validator->errors()->all() as $message) {
//                    throw new Exception($message, 1);
//                }
//            }
//
//            $min_sum_topup = Currency::convert($this->main_currency, $this->user->currency, $this->set_shop->min_sum_topup);
//            $main_sum = Currency::convert($this->user->currency, $this->main_currency, $request->sum);
//
//            if($request->sum < $min_sum_topup){
//                $alert_text = str_replace(':sum', $min_sum_topup.$this->currency_symbol, __('bot.alert_min_sum_topup'));
//                throw new Exception($alert_text);
//            }
//
//            $shop = Shop::getDefault();
//            if(!$shop){throw new Exception('Shop not found!');}
//
//            $member = Member::where('sid', $shop->id)->where('id', $this->user->id)->first();
//            if (!$member) {throw new Exception('Пользователь не найден.', 1);}
//
//            $payment_asset = PaymentAsset::where('id', $request->method_id)->where('active', 1)->first();
//            if (!$payment_asset) {
//                throw new Exception('Валюта не активна.', 1);
//            }
//
//            $payment_system = PaymentSystem::where('id', $payment_asset->psid)->where('active', 1)->first();
//            if (!$payment_system) {
//                throw new Exception('Платежная система не активна.', 1);
//            }
//
//            $expired_at = strtotime(date('Y-m-d H:i', strtotime('+'.$this->set_shop->booking_time.' minutes')));
//            $order_hash = mb_strtoupper(Str::random(config('app.order_hash_line')));
//            $delivery_hash = Str::random(config('app.delivery_hash_line'));
//
//            $sql = [
//                'sid' => $shop->id,
//                'pid' => 0,
//                'bid' => $member->id,
//                'tid' => 0,
//                'oid' => '0',
//                'title' => 'Пополнение баланса',
//                'amount' => $main_sum,
//                'currency' => 'USD',
//                'amount_rub' => 0,
//                'count_all' => 1,
//                'promo_id' => 0,
//                'is_sent' => 0,
//                'msg_id' => 0,
//                'method_payment' => '',
//                'status' => 1,
//                'type' => 1,
//                'hash' => $order_hash,
//                'delivery_hash' => $delivery_hash,
//                'payment_at' => 0,
//                'expired_at' => $expired_at,
//                'created_at' => strtotime('NOW')
//            ];
//
//            if(Order::create($sql)) {
//                return response()->json(['ok' => true, 'result' => ['hash' => $order_hash, 'payment_link' => config('app.url_invoice'). $order_hash .'/'.$payment_system->type.'/'.$request->method_id]]);
//            }
//
//        } catch (Exception $e) {
//            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
//        }
//    }

    public function create_topup(Request $request){
        try {

            $rules = [
                'sum' => 'required|numeric'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $date_now = Carbon::now()->timestamp;

            $amount = $request->get('sum');

            $min_sum_topup = $this->set_shop->min_sum_topup;
            $main_sum = Currency::convert($this->user->currency, $this->main_currency, $amount);

            if($this->user->currency == 'RUB'){
                $min_sum = Currency::convertToRub($this->main_currency, $this->user->currency, $this->set_shop->min_sum_topup);
            } else {
                $min_sum = $min_sum_topup;
            }

            if($main_sum < $min_sum_topup){
                $alert_text = str_replace(':sum', $min_sum.$this->currency_symbol, __('bot.alert_min_sum_topup'));
                throw new Exception($alert_text);
            }

            $order = Order::where('tid', 0)
                ->where('bid', $this->user->id)
                ->where('amount', $main_sum)
                ->where('status', 1)
                ->where('expired_at', '>', $date_now)
                ->first();


            if($order){

                return response()->json([
                    'ok' => true,
                    'result' => [
                        'id' => $order->id,
                        'hash' => $order->hash,
                        'amount' => Currency::convert($this->main_currency, $this->user->currency, $main_sum).$this->currency_symbol,
                        'amount_full' => Currency::convert($this->main_currency, $this->user->currency, $main_sum),
                        'currency' => $order->currency,
                    ]
                ]);
            }

            $order_hash = mb_strtoupper(Str::random(config('app.order_hash_line')));

            $sql = [
                'sid' => $shop->id,
                'pid' => 0,
                'bid' => $this->user->id,
                'tid' => 0,
                'oid' => '0',
                'title' => 'Пополнение баланса',
                'amount' => $main_sum,
                'amount_rub' => 0,
                'currency' => $this->set_shop->currency,
                'count_all' => 0,
                'promo_id' => 0,
                'is_sent' => 0,
                'msg_id' => 0,
                'method_payment' => '',
                'status' => 1,
                'type' => 1,
                'hash' => $order_hash,
                'delivery_hash' => '',
                'payment_at' => 0,
                'expired_at' => strtotime(date('Y-m-d H:i', strtotime('+'.$this->set_shop->booking_time.' minutes'))),
                'created_at' => $date_now,
            ];

            $insert_id = Order::insertGetId($sql);

            if ($insert_id) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Заказ создан.',
                    'result' => [
                        'id' => $insert_id,
                        'hash' => $order_hash,
                        'amount' => Currency::convert($this->main_currency, $this->user->currency, $main_sum).$this->currency_symbol,
                        'amount_full' => Currency::convert($this->main_currency, $this->user->currency, $main_sum),
                        'currency' => $this->set_shop->currency,
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage(), 'line' => $e->getLine()], 200);
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
            $method_payment = $request->input('method_payment');

            if($column_name == 'buyer'){$column_name = 'tid';}

            $access = RolePermission::getByPermission($this->user->role_id, 'orders.all')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Shop not found!');
            }

            $tg = new Api(Crypt::decryptString($shop->token));

            if ($column_name && $order_dir) {
                $orders = Order::orderBy($column_name, $order_dir);
            } else {
                $orders = Order::orderBy('created_at', 'DESC');
            }

            if($status != null){
                $orders = $orders->where('status', $status);
            }

            if($method_payment != null){
                $orders = $orders->where('method_payment', $method_payment);
            }

            if ($search_term) {
                $orders = $orders->where(function($query) use ($search_term) {
                    $query->where('hash', 'LIKE', '%' . $search_term . '%')
                    ->orWhere('title', 'LIKE', '%' . $search_term . '%');

                    $member = Member::where('username', 'LIKE', '%' . $search_term . '%')->orWhere('tid', 'LIKE', $search_term)->first();

                    if ($member) {
                        $query->orWhere('bid', $member->id);
                    }
                });
            }

            $filtered_count = $orders->count();
            $orders = $orders->offset($start)->limit($length)->get();
            $total_count = Order::getCountAll();

            $all = [];

            foreach ($orders as $o) {

                $buyer = Member::getByID($o->bid, $o->sid);

                $block_buyer = '-';
                if ($buyer) {
                    $block_buyer = $buyer->username;
                }

                $block_pay = '-';

                if ($o->method_payment != '' and $o->method_payment != 'bc') {
                    $method = PaymentSystem::getByType($o->method_payment);
                    $block_pay = $method->title;
                } else if ($o->method_payment == 'bc') {
                    $block_pay = 'Баланс';
                }

                $p = Product::where('id', $o->pid)->first();

                $block_title = $o->title;
                if ($p) {
                    $hack_status = HackStatus::getByID($p->hack_status);

                    $status = '';
                    if ($hack_status->title_pub != '') {
                        $status = '(' . $hack_status->title_pub . ')';
                    }

                    $block_title = $p->title . ' ' . $status;
                }

                if ($o->status == 1) {
                    $block_status = '<span class="badge bg-warning px-2" style="color: #000">В процессе</span>';
                    $block_params = 'class="text-gray-700"';
                    $payment_at = $this->formatDateTimeZone($o->created_at, $this->timezone);
                    $block_cancel = ' <a title="Отменить заказ" href="javascript:;" onclick="cancelOrder('.$o->id.')" style="color:#E74C3C"><i class="far fa-times-circle fa-xl"></i></a>';
                }
                if ($o->status == 2) {
                    $block_status = '<span class="badge bg-success px-2" style="color: #000">Оплачен</span>';
                    $block_params = 'data-toggle="modal" data-target="#getOrder"';
                    $payment_at = $this->formatDateTimeZone($o->payment_at, $this->timezone);
                    $block_cancel = '';
                }
                if ($o->status == 3) {
                    $block_status = '<span class="badge bg-danger px-2" style="color: #fff">Отменен</span>';
                    $block_params = 'class="text-gray-700"';
                    $payment_at = $this->formatDateTimeZone($o->created_at, $this->timezone);
                    $block_cancel = '';
                }
                if ($o->status == 4) {
                    $block_status = '<span class="badge bg-secondary px-2" style="color: #000">Истек срок</span>';
                    $block_params = 'class="text-gray-700"';
                    $payment_at = $this->formatDateTimeZone($o->created_at, $this->timezone);
                    $block_cancel = '';
                }

                $all[] = [
                    'id' => '<b style="font-size:14px" class="text-primary">' . $o->hash . '</b>',
                    'icon' => '<i class="far fa-file-invoice-dollar"></i>',
                    'title' => $block_title,
                    'buyer' => $block_buyer,
                    'count' => $o->count_all,
                    'amount' => $o->amount . $this->def_currency_symbol,
                    'block_pay' => $block_pay,
                    'status' => $block_status,
                    'created_at' => $payment_at,
                    'block_link' => '<a title="Получить заказ" data-id="' . $o->id . '" ' . $block_params . ' target="_blank" href="javascript:;"><i class="far fa-cloud-download fa-xl"></i></a>' . $block_cancel
                ];
            }

            return response()->json(['data' => $all, 'recordsTotal' => $total_count, 'recordsFiltered' => $filtered_count]);
//
//
//            return datatables($all)
//                ->rawColumns(['id', 'buyer', 'block_link', 'status'])
//                ->make(true);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage(), 'line' => $e->getLine()], 200);
        }
    }
    public function salesTop()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'analytics.get')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $products = DB::table('products')
                ->where('sid', $shop->id)
                ->where('count_sales', '!=', 0)
                ->orderByDesc('count_sales')
                ->limit(5)
                ->get();

            $all = [];

            foreach ($products as $p) {

                $count_profits = DB::table('orders')
                    ->where('sid', $shop->id)
                    ->where('pid', $p->id)
                    ->where('status', 2)
                    ->sum('amount');

                $image = '/icheat';

                if($p->image != ''){
                    $image = '/i'.$p->image_site;
                }

                $hack_status = HackStatus::getByID($p->hack_status);

                $status = '';
                if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                $all[] = [
                    'title' => '<div style="border-radius: 50%;overflow: hidden; width: 30px; height: 30px;float: left; margin-right: 12px"><img height="30" src="'.$image.'" /></div><div class="d-block" style="margin-top: 6px"><b>'.$p->title.' '.$status.'</b></div>',
                    'sales' => $p->count_sales,
                    'sum' => number_format($count_profits, 2, '.', ' ').$this->def_currency_symbol,
                    'count_views' => $p->count_views,
                ];
            }

            return datatables($all)
                ->rawColumns(['title', 'image'])
                ->make(true);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }

    }

    public function download($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'orders.download')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $order = DB::table('orders')
                ->where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if (!$order) {
                throw new Exception('Order not found!', 1);
            }

            $materials = DB::table('materials')
                ->where('sid', $shop->id)
                ->where('oid', $id)
                ->get();

            $body = [];

            foreach ($materials as $m) {
                $body[] = $m->body;
            }

            $path = 'public/files/';
            $filename = Str::random(16);

            if(Storage::disk('local')->put($path.$filename.'.txt', implode("\n", $body))){
                return Storage::download($path.$filename.'.txt');
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function get($id){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $order = DB::table('orders')
                ->where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if(!$order){
                throw new Exception('Order not found!', 1);
            }

            if($order->status == 1){
                throw new Exception('Order not yet paid!', 1);
            }

            $product = Product::where('id', $order->pid)->where('sid', $shop->id)->first();

            if(!$product){
                throw new Exception('Product not found!', 1);
            }

            $member = Member::where('id', $order->bid)->where('sid', $shop->id)->first();

            if(!$member){
                throw new Exception('Member not found!', 1);
            }

            $materials = Material::where('sid', $shop->id)
                ->where('pid', $order->pid)
                ->where('tid', $order->tid)
                ->where('oid', $order->id)
                ->where('status', 2)
                ->get();

            $body = [];

            foreach ($materials as $m) {
                $body[] = $m->body;
            }

            return response()->json([
                'ok' => true,
                'result' => [
                    'id' => $order->hash,
                    'count' => $order->count_all,
                    'body' => implode("\n", $body),
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function payment(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'bid' => 'required|int|min:1',
                'id' => 'required|int|min:1'
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $date_now = Carbon::now()->timestamp;

            $order = DB::table('orders')
                ->where('bid', $request->bid)
                ->where('id', $request->id)
                ->first();

            if(!$order){
                throw new Exception('Order not found!', 1);
            }

            if($order->status == 2){
                throw new Exception('Order payment!', 1);
            }

            $shop = Shop::where('id', $order->sid)->first();

            if(!$shop){
                throw new Exception('Shop not found!', 1);
            }

            $product = Product::where('id', $order->pid)->where('sid', $shop->id)->first();

            if(!$product){
                throw new Exception('Product not found!', 1);
            }

            $member = Member::where('id', $order->bid)->where('sid', $shop->id)->first();

            if(!$member){
                throw new Exception('Member not found!', 1);
            }

            if($order->expired_at < $date_now){
                throw new Exception('Payment time has expired!', 1);
            }

            $sql = ['status' => 2, 'payment_at' => $date_now];

            $result = Order::where('id', $request->id)->update($sql);

            if ($result) {

                Product::where('id', $order->pid)->increment('count_sales',$order->count_all);

                if($product->is_endless == 0 && $order->type != 1){
                     // site orders (type=1) already decremented at creation (booking)
                     Product::where('id', $order->pid)->decrement('count_all',$order->count_all);
                }

                Material::markSoldForOrder($order->pid, $order->tid, $order->id, $order->count_all);

                $body = [];
                $materials = Material::where('oid', $order->id)->where('status', 2)->get();
                foreach ($materials as $m) {
                    $body[] = $m->body;
                }

                return response()->json([
                    'ok' => true,
                    'description' => 'Order payment!',
                    'result' => [
                        'count' => $order->count_all,
                        'amount' => $order->amount,
                        'currency' => $order->currency,
                        'body' => implode("\n", $body),
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function cancel_admin($id){
        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $order = Order::where('sid', $shop->id)->where('id', $id)->first();
            if(!$order){
                throw new Exception('Заказ не найден.', 1);
            }
            if($order->status != 1){
                throw new Exception('Отменить можно только неоплаченный заказ.', 1);
            }
            Order::editByHash($order->hash, $shop->id, 'status', 3);
            if($order->pid > 0){
                Product::where('id', $order->pid)->increment('count_all', $order->count_all);
            }
            Material::releaseFromOrder($order->id);
            return response()->json(['ok' => true, 'description' => 'Заказ отменён']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
}


