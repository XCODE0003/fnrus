<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\HackStatus;
use App\Models\PaymentAsset;
use App\Models\PaymentSystem;
use App\Models\Refil;
use App\Models\Text;
use Exception;
use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Material;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\MethodPayment;
use App\Models\ShopSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\FileUpload\InputFile;
use Stripe\Stripe;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class InvoiceController extends Controller
{

    public $shop_currency = ['RUB' => '₽', 'USD' => '$'];

    public function index($hash){

        $shop = Shop::getDefault();

        $order = Order::where('hash', $hash)->first();
        if(!$order) {return false;}

        if($order->status == 1 && $order->expired_at > strtotime('NOW')) {
            return view('user.invoice', ['title' => $order->title, 'amount' => $order->amount, 'hash' => $hash]);
        } else {
            return view('user.check', ['title' => $order->title, 'amount' => $order->amount, 'hash' => $hash, 'status' => $order->status, 'shop_username' => $shop->username, 'expired_at' => $order->expired_at, 'date_now' => strtotime('NOW')]);
        }
    }

    private function _isAjax(){
        return request()->ajax() || request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest';
    }

    private function _methodFail($hash, $msg){
        if ($this->_isAjax()) {
            return response()->json(['ok' => false, 'message' => $msg], 200);
        }
        return redirect('/invoice/'.$hash.'?warning='.urlencode($msg));
    }

    private function _methodSuccess($hash, $url){
        if ($this->_isAjax()) {
            return response()->json(['ok' => true, 'redirect' => $url], 200);
        }
        return redirect($url);
    }

    public function method($hash, $type, $asset_id){

        $o = Order::where('hash', $hash)->where('status', 1)->first();
        if(!$o) {
            return $this->_methodFail($hash, 'Заказ не найден или уже не активен.');
        }

        $s = Shop::getDefault();

        $product = Product::getByID($o->sid, $o->pid);
        $m = Member::getByID($o->bid, $o->sid);

        if($product) {
            $title = $product->title;
        } else {
            $title = 'Пополнение баланса';
        }

        $mp = MethodPayment::where('type', $type)->first();
        if(!$mp){
            return $this->_methodFail($hash, 'Метод оплаты не активен!');
        }

        $pa = PaymentAsset::where('psid', $mp->psid)->where('id', $asset_id)->where('active', 1)->first();
        if(!$pa){
            return $this->_methodFail($hash, 'Валюта не активна!');
        }

        $currency = $pa->currency;
        $method = $pa->code;

        $set_shop = ShopSettings::getDefault();
        $main_currency = $set_shop->currency;


        if ($o->bid != 0) {
            $currency_symbol = $this->shop_currency[$m->currency];
            $currency_code = $m->currency;
        } else {
            $currency_symbol = $this->shop_currency[$set_shop->currency];
            $currency_code = $set_shop->currency;
        }

        if($currency == 'USDT'){
            $amount = $o->amount;
        } else {
            $amount = Currency::convert($main_currency, $currency, $o->amount);
        }

        // Per-asset min/max enforcement (admin: editAsset settings)
        // Skip check when conversion rate is unavailable (e.g. XTR/Stars, KGS) — otherwise
        // $amount becomes null/0 and would falsely trigger the min check for any order.
        if (!empty($amount) && $amount > 0) {
            if ($pa->min > 0 && $amount < $pa->min) {
                $min_in_main = Currency::convert($pa->currency, $main_currency, $pa->min);
                $min_fmt = !empty($min_in_main) ? rtrim(rtrim(number_format($min_in_main, 2, '.', ''), '0'), '.') . ' ' . $main_currency : rtrim(rtrim(number_format($pa->min, 2, '.', ''), '0'), '.') . ' ' . $pa->currency;
                $msg = 'Минимальная сумма оплаты для данного способа — ' . $min_fmt . '. Пожалуйста, увеличьте сумму заказа или выберите другой способ оплаты.';
                return $this->_methodFail($hash, $msg);
            }
            if ($pa->max > 0 && $amount > $pa->max) {
                $max_in_main = Currency::convert($pa->currency, $main_currency, $pa->max);
                $max_fmt = !empty($max_in_main) ? rtrim(rtrim(number_format($max_in_main, 2, '.', ''), '0'), '.') . ' ' . $main_currency : rtrim(rtrim(number_format($pa->max, 2, '.', ''), '0'), '.') . ' ' . $pa->currency;
                $msg = 'Максимальная сумма оплаты для данного способа — ' . $max_fmt . '. Пожалуйста, уменьшите сумму заказа или выберите другой способ оплаты.';
                return $this->_methodFail($hash, $msg);
            }
        }

        $booking_time = $set_shop->booking_time;

        if($o->type === 0) {
            $successUrl = 'https://t.me/'.$s->username;
            $cancelUrl = 'https://t.me/'.$s->username.'?start=support';
        }

        if($o->type === 1) {
            $successUrl = config('app.url');
            $cancelUrl = config('app.url') . '/#faq';
        }

        $link_pay = null;

        if($type == 'qw'){
            $oid = $o->oid;
            if($o->oid == '0') {
                $data = $this->createInvoiceQiwi($amount, $currency, $o->hash, $mp->theme_code, $mp->public_key, $booking_time);
                if (!empty($data['status'])) {
                    return $this->_methodFail($hash, $data['message']);
                }
                if (!empty($data['invoice_uid'])) {
                    Order::where('hash', $hash)->update(['oid' => $data['invoice_uid']]);
                    $oid = $data['invoice_uid'];
                }
            }
            $link_pay = 'https://oplata.qiwi.com/form?invoice_uid='.$oid.'&successUrl='.$successUrl;
        }

        Order::editByHash($o->hash, $s->id, 'method_payment', $type);

        if($type == 'et'){
            $link_pay = $this->createInvoiceEnot($mp->public_id,$amount,$currency,$hash,$successUrl,$mp->secret_key);
        }

        if($type == 'fk'){
            $link_pay = $this->createInvoiceFK($mp->public_id, $amount, $currency, $hash, $successUrl, $cancelUrl, $mp->secret_key, $mp->public_key, request()->ip(), null, $method);
        }

        if($type == 'rk'){
            $link_pay = $this->createInvoiceRK($mp->public_id,$amount,$hash,$mp->secret_key);
        }

        if($type == 'ap'){
            $link_pay = $this->createInvoiceAnyPay($mp->public_id, $amount, $currency, $method, $hash, $mp->secret_key, $m->locale);
        }

        if($type == 'cb'){
            $cb_assets = 'USDT';
            if(!empty($mp->assets)){
                $decoded_assets = json_decode($mp->assets, true);
                if(is_array($decoded_assets) && count($decoded_assets) > 0){
                    $cb_assets = implode(',', array_map('strtoupper', $decoded_assets));
                }
            }
            $link_pay = $this->createInvoiceCrypto($cb_assets, $o->amount, $hash, $successUrl, $mp->secret_key, $main_currency);
        }

        if($type == 'ts'){
            $stars_rate = (!empty($mp->secret_key_two) && $mp->secret_key_two != '0') ? (float)$mp->secret_key_two : (float)config('app.telegram_stars_rate', 1.3);
            $link_pay = $this->createInvoiceStars($o->amount, $hash, $title, $mp->secret_key, $stars_rate);
        }

        if($type == 'bt'){
            $btResult = $this->createInvoiceBT($mp->public_key, $mp->secret_key, $amount, $currency, $method, $hash, $successUrl, $m->locale);

            if(!empty($btResult['errors']['amount'])){
                $bt_msg = $btResult['errors']['amount'][0];
                // BT returns English error like "min amount is 25000" — parse the actual
                // BT-side minimum and convert to main currency for a unified message.
                $bt_min = null;
                if (preg_match('/(\d+(?:\.\d+)?)/', $bt_msg, $m)) {
                    $bt_min = (float)$m[1];
                }
                if ($bt_min !== null && $bt_min > 0) {
                    $min_in_main = Currency::convert($pa->currency, $main_currency, $bt_min);
                    $min_fmt = !empty($min_in_main) ? rtrim(rtrim(number_format($min_in_main, 2, '.', ''), '0'), '.') . ' ' . $main_currency : rtrim(rtrim(number_format($bt_min, 2, '.', ''), '0'), '.') . ' ' . $pa->currency;
                    $msg = 'Минимальная сумма оплаты для данного способа — ' . $min_fmt . '. Пожалуйста, увеличьте сумму заказа или выберите другой способ оплаты.';
                    return $this->_methodFail($hash, $msg);
                }
                return $this->_methodFail($hash, $bt_msg);
            }

            if(!empty($btResult['urlPayment'])){
                $link_pay = $btResult['urlPayment'];
            } else {
                \Log::error('BTKassa invoice create failed', [
                    'response' => $btResult,
                    'amount' => $amount,
                    'order_id' => $hash,
                ]);
            }

        }

        if($type == 'ai'){
            $link_pay = $this->createInvoiceAaio($mp->public_id, $amount, $currency, $method, $hash, $mp->secret_key, $m->locale);
        }

        if($type == 'cp'){
            $link_pay = $this->createInvoiceCrystalPay($mp->public_id, $amount, $currency, $hash, $mp->secret_key, $successUrl);
        }

        if($type == 'bn'){
            $link_pay = $this->createInvoiceBinance($title, $mp->public_key, $amount, $currency, $hash, $mp->secret_key, $successUrl, $cancelUrl);
        }

        if($type == 'sp'){
            $link_pay = $this->createInvoiceSP($title, $o->amount, $currency, $hash, $mp->secret_key, $successUrl, $cancelUrl);
        }

        if($type == 'sm'){
            $link_pay = $this->createInvoiceStreamPay($mp->public_id, $o->amount, $main_currency, $hash, $mp->secret_key, $successUrl);
        }

        if($type == 'pp'){
            $link_pay = $this->createInvoicePayPalych($mp->public_id, $amount, $currency, $hash, $mp->secret_key);
        }

        if($type == 'lv'){

            $data = [
                "customFields" => "None",
                "expire" => $booking_time,
                "failUrl" => $successUrl,
                "hookUrl" => config('app.url'),
                "includeService" => ["card", "sbp", "qiwi"],
                "orderId" => $hash,
                'shopId' => $mp->public_id,
                "successUrl" =>  $successUrl,
                'sum' => $amount,
            ];
            $result = $this->createInvoiceLava($data,$mp->secret_key);
            if($result){
                $link_pay = $result;
            } else {
                return $this->_methodFail($hash, 'Не удалось создать платёж. Попробуйте другой способ оплаты.');
            }
        }

        if ($link_pay === 'min_amount') {
            $min_in_main = Currency::convert($pa->currency, $main_currency, $pa->min);
            $min_fmt = !empty($min_in_main) ? rtrim(rtrim(number_format($min_in_main, 2, '.', ''), '0'), '.') . ' ' . $main_currency : rtrim(rtrim(number_format($pa->min, 2, '.', ''), '0'), '.') . ' ' . $pa->currency;
            $msg = 'Минимальная сумма оплаты для данного способа — ' . $min_fmt . '. Пожалуйста, увеличьте сумму заказа или выберите другой способ оплаты.';
            return $this->_methodFail($hash, $msg);
        }

        if ($link_pay) {
            return $this->_methodSuccess($hash, $link_pay);
        } else {
            return $this->_methodFail($hash, 'Платежная ссылка не найдена!');
        }

    }
//
//    public function get($hash) {
//
//        $o = Order::where('hash', $hash)->first();
//        if(!$o) {return response()->json(['ok' => false]);}
//
//        if($o->status == 1){
//            return response()->json(['ok' => 'wait']);
//        }
//
//        if($o->status == 2 && $o->is_sent == 1){
//            return response()->json(['ok' => 'is_sent']);
//        }
//
//        $s = Shop::where('id', $o->sid)->first();
//
//        if($o->status == 2){
//
//            $shop_token = Crypt::decryptString($s->token);
//
//            $tg = new Api($shop_token);
//            $tg->setAsyncRequest(true);
//
//            $cid = $o->bid;
//            $sid = $s->id;
//
//            if($o->type == 0) {
//                $message_text = str_replace(':order_hash', $hash, __('bot.alert_order_paid'));
//
//                $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $o->id . "/get"];
//                $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $o->pid . "/1"];
//
//                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
//                $res = file_get_contents('https://api.telegram.org/bot' . $shop_token . '/sendMessage?chat_id=' . $cid . '&text=' . $message_text . '&parse_mode=HTML&reply_markup=' . $kp);
//                $decode = json_decode($res, true);
//                $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $decode['result']['message_id'] - 1]);
//            }
//
//            if($o->type == 1) {
//                $message_text = str_replace(':sum', $main_sum, __('bot.alert_balance_plus'));
//
//                $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];
//
//                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
//                $res = file_get_contents('https://api.telegram.org/bot' . $shop_token . '/sendMessage?chat_id=' . $cid . '&text=' . $message_text . '&parse_mode=HTML&reply_markup=' . $kp);
//                $decode = json_decode($res, true);
//                $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $decode['result']['message_id'] - 1]);
//            }
//
//            Order::editByHash($hash, $sid, 'is_sent', 1);
//            return response()->json(['ok' => 'paid']);
//        }
//    }


    public function status($hash) {

        $o = Order::where('hash', $hash)->first();
        if(!$o) {return response()->json(['ok' => false]);}

        if($o->status == 3){
            return response()->json(['ok' => 'cancelled']);
        }

        if($o->status == 4){
            return response()->json(['ok' => 'expired']);
        }

        if($o->status == 1 && $o->expired_at > 0 && $o->expired_at <= time()){
            $o = Order::expirePendingById((int) $o->id) ?? $o->fresh();
            return response()->json(['ok' => 'expired']);
        }

        if($o->status == 1 && $o->is_sent == 0){
            return response()->json(['ok' => 'wait']);
        }

        if($o->status == 2 && $o->is_sent == 1){
            return response()->json(['ok' => 'is_sent']);
        }

        if($o->status == 2 && $o->is_sent == 0){
            return response()->json(['ok' => 'paid']);
        }
    }


    public function convertTest(){
        return Currency::cron_convert();
    }
//    public function qwCheck(Request $request){
//
//        $json = file_get_contents('php://input');
//        $data = json_decode($json, true);
//
//        $amount = floatval($data['bill']['amount']['value']);
//        $currency = $data['bill']['amount']['currency'];
//        $status = $data['bill']['status']['value'];
//        $comment = $data['bill']['comment'];
//
//        // Оплата заказа
//        $o = Order::where('hash', $comment)->where('status', 1)->first();
//        if($o) {
//            $s = Shop::where('id', $o->sid)->first();
//            $p = Product::getByID($s->id, $o->pid);
//            $mp = MethodPayment::where('sid', $o->sid)->where('type', 'qw')->first();
//            $order_amount = floatval($o->amount);
//
//            $signature = $request->header('X-Api-Signature-SHA256');
//            $secret_key = $mp->secret_key;
//
//            $params = $data['bill']['amount']['currency'].'|'.$data['bill']['amount']['value'].'|'.$data['bill']['billId'].'|'.$data['bill']['siteId'].'|'.$data['bill']['status']['value'];
//
//            if ($signature !== hash_hmac('sha256', $params, $secret_key)) {
//                return response()->json(['error' => 'Unauthorized'], 401);
//            }
//
//            if ($status === 'PAID' && $amount === $order_amount && $currency === 'RUB') {
//
//                $date_now = strtotime('NOW');
//
//                if($o->type == 0) {
//
//                    Product::where('id', $o->pid)->increment('count_sales', $o->count_all);
//
//                    if ($p->is_endless == 0) {
//                        Product::where('id', $o->pid)->decrement('count_all', $o->count_all);
//                    }
//
//                    $materials = Material::where('sid', $s->id)
//                        ->where('tid', $o->tid)
//                        ->where('pid', $o->pid)
//                        ->where('status', 1)
//                        ->limit($o->count_all)
//                        ->get();
//
//                    foreach ($materials as $item) {
//                        if ($p->is_endless == 1) {
//
//                            $sql = [
//                                'sid' => $s->id,
//                                'pid' => $o->pid,
//                                'eid' => 0,
//                                'oid' => $o->id,
//                                'bid' => $o->bid,
//                                'body' => htmlspecialchars($item->body, ENT_QUOTES),
//                                'status' => 2,
//                                'created_at' => $date_now,
//                            ];
//                            Material::create($sql);
//                        }
//
//                        if ($p->is_endless == 0) {
//                            Material::where('id', $item->id)
//                                ->update(['oid' => $o->id, 'status' => 2]);
//                        }
//                    }
//
//                    if ($o->promo_id != 0) {
//                        Coupon::where('id', $o->promo_id)->where('sid', $s->id)->decrement('count_uses_max', 1);
//                        CouponUse::create(['promo_id' => $o->promo_id, 'chat_id' => $o->bid, 'shop_id' => $s->id, 'created_at' => $date_now]);
//                    }
//                }
//
//                if($o->type == 1) {
//                    Member::where('id', $o->bid)->where('sid', $o->sid)->increment('balance_main', $amount);
//                }
//
//                Order::where('hash', $o->hash)->update(['method_payment' => 'qw', 'status' => 2, 'payment_at' => strtotime('NOW')]);
//
//                $this->get($o->hash);
//
//                return 'PAID';
//            }
//        }
//
//        return response()->json(['error' => 0]);
//    }

    public function createInvoiceAaio($merchant_id, $amount, $currency, $method, $hash, $secret, $locale){

        $sign = hash('sha256', implode(':', [$merchant_id, $amount, $currency, $secret, $hash]));

        return 'https://aaio.io/merchant/pay?' . http_build_query([
                'merchant_id' => $merchant_id,
                'amount' => $amount,
                'method' => $method,
                'currency' => $currency,
                'order_id' => $hash,
                'desc' => '',
                'sign' => $sign,
                'lang' => mb_strtolower($locale)
            ]);
    }

    public function createInvoiceSP($product_title, $amount, $currency, $hash, $secret_key, $success_url, $cancel_url){

        Stripe::setApiKey($secret_key);

        try {
            // Создаем заказ в Stripe
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => $this->convertToCents($amount),
                            'product_data' => [
                                'name' => $product_title,
                            ],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'payment_intent_data' => ['metadata' => ['order_id' => $hash]],
                'mode' => 'payment',
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
            ]);


            // Получаем ссылку на оплату заказа
            $paymentLink = $session->url;

//            $shop = Shop::getDefault();

//            Order::editByHash($hash, $shop->id, 'oid', $session->id);

//            dd($session);

            return $paymentLink;

        } catch (ApiErrorException $e) {
            dd($e->getMessage());
        }

    }

    public function createInvoiceStreamPay($public_id, $amount, $currency, $hash, $secret_key, $successUrl){

        // StreamPay only accepts USDT — convert from shop currency via USD ≈ USDT
        if($currency != 'USDT' && $currency != 'USD'){
            $usdtAmount = Currency::convert($currency, 'USD', $amount);
            if(!$usdtAmount || $usdtAmount <= 0){
                \Log::error('StreamPay: failed to convert currency to USDT', [
                    'from' => $currency,
                    'amount' => $amount,
                ]);
                return false;
            }
            $amount = round($usdtAmount, 2);
        }

        // StreamPay requires minimum 2.5 USDT
        if ($amount < 2.5) {
            \Log::warning('StreamPay: amount below minimum 2.5 USDT', [
                'amount_usdt' => $amount,
                'order_id' => $hash,
            ]);
            return 'min_amount';
        }

        $body = json_encode([
            'store_id' => (int)$public_id,
            'customer' => 'customer@fnrus.com',
            'external_id' => $hash,
            'description' => 'Payment ' . $hash,
            'system_currency' => 'USDT',
            'payment_type' => 2,
            'amount' => (float)$amount,
        ]);

        // Ed25519 signature: body + YYYYMMDD:HHmm (UTC)
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $textToSign = $body . $now->format('Ymd:Hi');

        // Build Ed25519 keypair from seed (first 64 hex chars = 32 bytes)
        $seed = hex2bin(substr($secret_key, 0, 64));
        $keypair = sodium_crypto_sign_seed_keypair($seed);
        $signingKey = sodium_crypto_sign_secretkey($keypair);
        $signature = bin2hex(sodium_crypto_sign_detached($textToSign, $signingKey));

        $response = Http::withHeaders([
            'signature' => $signature,
            'Content-Type' => 'application/json',
        ])->withBody($body, 'application/json')
          ->post('https://api.streampay.org/api/payment/create');

        $resp = json_decode($response, true);

        if($response->status() == 200 && !empty($resp['data']['pay_url'])){
            return $resp['data']['pay_url'];
        }

        \Log::error('StreamPay invoice create failed', [
            'http_status' => $response->status(),
            'response' => $resp,
            'amount' => $amount,
            'order_id' => $hash,
            'shop_id' => $public_id,
        ]);

        return false;
    }

    public function createInvoicePayPalych($public_id, $amount, $currency, $hash, $secret_key){

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$secret_key,
        ])->asForm()->post('https://pally.info/api/v1/bill/create', [
            'amount' => $amount,
            'description' => '',
            'order_id' => $hash,
            'type' => 'multi',
            'shop_id' => $public_id,
            'custom' => '',
            'currency_in' => $currency,
        ]);

        $resp = json_decode($response, true);

        \Log::info('PayPalych invoice create response', [
            'http_status' => $response->status(),
            'response' => $resp,
            'order_id' => $hash,
            'shop_id' => $public_id,
            'amount' => $amount,
        ]);

        if(!empty($resp['link_url'])){
            return $resp['link_url'];
        }

        \Log::error('PayPalych invoice create failed', [
            'response' => $resp,
            'order_id' => $hash,
            'shop_id' => $public_id,
        ]);

        return false;

    }

    public function createInvoiceCrystalPay($public_id, $amount, $currency, $order_id, $secret, $success_url){

        $curl = curl_init();

        $data = [
            'auth_login' => $public_id,
            'auth_secret' => $secret,
            'type' => 'purchase',
            'amount' => (float)$amount,
            'lifetime' => 4300,
            'currency' => $currency,
            'extra' => $order_id,
            'redirect_url' => $success_url,
            'callback_url' => config('app.url').'/pay/callback/cp',
        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.crystalpay.io/v3/invoice/create/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $decoded = json_decode($response, true);

        if(!empty($decoded['error']) && $decoded['error'] == true){
            \Log::error('CrystalPay invoice create failed', [
                'response' => $decoded,
                'amount' => $amount,
                'order_id' => $order_id,
            ]);
            return null;
        }

        return $decoded['url'];

    }

    public function createInvoiceBinance($title, $public_key, $amount, $currency, $order_id, $secret, $returnUrl, $cancelUrl)
    {

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $nonce = '';
        for($i=1; $i <= 32; $i++)
        {
            $pos = mt_rand(0, strlen($chars) - 1);
            $char = $chars[$pos];
            $nonce .= $char;
        }

        $timestamp = round(microtime(true) * 1000);

        $data = array(
            "env" => [
                "terminalType" => "APP"
            ],
            "merchantTradeNo" => mt_rand(982538,9825382937292),
            "orderAmount" => $amount,
            "currency" => $currency,
            "goods" => [
                "goodsType" => "02",
                "goodsCategory" => "6000",
                "goodsName" => 'Order: '.$order_id,
                "referenceGoodsId" => $order_id,
            ],
            "returnUrl" => $returnUrl,
            "cancelUrl" => $cancelUrl,
            "webhookUrl" => config('app.url').'/pay/callback/bn'
        );


        $json_request = json_encode($data);
        $payload = $timestamp."\n".$nonce."\n".$json_request."\n";
        $signature = strtoupper(hash_hmac('sha512',$payload,$secret));
        $response = \Http::withHeaders([
            "Content-Type"=>"application/json",
            "BinancePay-Timestamp"=>$timestamp,
            "BinancePay-Nonce"=>$nonce,
            "BinancePay-Certificate-SN"=>$public_key,
            "BinancePay-Signature"=>$signature,
        ])->post('https://bpay.binanceapi.com/binancepay/openapi/v2/order',$data)->json();


        if($response['status'] == "SUCCESS"){
            return $response['data']['checkoutUrl'];
        } else {
            dd($response);
        }
    }


    public function checkInvoiceBinance($order_id, $public_key, $secret_key)
    {

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $nonce = '';
        for($i=1; $i <= 32; $i++)
        {
            $pos = mt_rand(0, strlen($chars) - 1);
            $char = $chars[$pos];
            $nonce .= $char;
        }

        $timestamp = round(microtime(true) * 1000);

        $data = ["merchantTradeNo" => $order_id];

        $json_request = json_encode($data);
        $payload = $timestamp."\n".$nonce."\n".$json_request."\n";
        $signature = strtoupper(hash_hmac('sha512',$payload,$secret_key));
        $response = \Http::withHeaders([
            "Content-Type"=>"application/json",
            "BinancePay-Timestamp"=>$timestamp,
            "BinancePay-Nonce"=>$nonce,
            "BinancePay-Certificate-SN"=>$public_key,
            "BinancePay-Signature"=>$signature,
        ])->post('https://bpay.binanceapi.com/binancepay/openapi/v2/order/query',$data)->json();

        if($response['code'] == '000000'){
            return true;
        }

    }

    public function createInvoiceCrypto($asset,$amount,$payload,$paid_btn_url,$token,$fiat_currency = 'RUB'){

        $body = [
            'currency_type' => 'fiat',
            'fiat' => $fiat_currency,
            'accepted_assets' => $asset,
            'amount' => (string)$amount,
            'payload' => $payload,
            'paid_btn_name' => 'callback',
            'paid_btn_url' => $paid_btn_url,
            'expires_in' => 600,
        ];

        $base_url = config('app.cryptobot_api_url', 'https://pay.crypt.bot');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $base_url . '/api/createInvoice',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => array(
                'Crypto-Pay-API-Token: '.$token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $data = json_decode($response, true);

        if(!empty($data['ok']) && $data['ok'] === true && !empty($data['result'])){
            // bot_invoice_url opens directly in Telegram bot, web_app_invoice_url as fallback
            return $data['result']['bot_invoice_url']
                ?? $data['result']['web_app_invoice_url']
                ?? $data['result']['pay_url']
                ?? null;
        }

        return null;

    }

    /**
     * Create a Telegram Stars invoice link via Telegram Bot API.
     *
     * @param float  $amount_rub  Amount in RUB
     * @param string $payload     Order hash
     * @param string $title       Product title
     * @param string $bot_token   Telegram Bot token
     * @return string|null        Invoice URL or null on failure
     */
    public function createInvoiceStars($amount_rub, $payload, $title, $bot_token, $rate = null){

        $rate = $rate ?? (float) config('app.telegram_stars_rate', 1.3);
        $stars_amount = max(1, (int) ceil($amount_rub / $rate));

        $body = [
            'title' => $title,
            'description' => 'Оплата заказа #' . $payload,
            'payload' => $payload,
            'provider_token' => '',
            'currency' => 'XTR',
            'prices' => json_encode([
                ['label' => $title, 'amount' => $stars_amount]
            ]),
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $bot_token . '/createInvoiceLink',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if (!empty($data['ok']) && $data['ok'] === true && !empty($data['result'])) {
            return $data['result'];
        }

        \Log::error('Telegram Stars createInvoiceLink failed', [
            'response' => $data,
            'amount_rub' => $amount_rub,
            'stars_amount' => $stars_amount,
            'payload' => $payload,
        ]);

        return null;
    }

    public function signature($data, $secretKey)
    {
        ksort($data);
        $signature = hash_hmac("sha256", json_encode($data), $secretKey);
        $data['signature'] = $signature;
        return $data;
    }

    public function referralFees($tg, $member, $sid, $order, $main_currency){
        $ref_id = $member->rid;
        $m_ref = Member::getByID($ref_id, $sid);

        if(isset($m_ref)) {
            $percent = $m_ref->ref_percent / 100;
            $balance = $order->amount * $percent;
            $ref_currency = $this->shop_currency[$m_ref->currency];
            $sum_currency = Currency::convert($main_currency, $m_ref->currency, $balance);
            $ref_balance = Currency::convert($main_currency, $m_ref->currency, $balance + $m_ref->balance_affiliate);

            Member::where('id', $ref_id)->where('sid', $sid)->increment('balance_affiliate', $balance);
            Refil::insertGetId(['sid' => $sid, 'owner_id' => $member->id, 'user_id' => $ref_id, 'sum' => $balance, 'created_at' => strtotime('NOW')]);

            if ($m_ref->tid > 0) {
                $tg->sendMessage(['chat_id' => $m_ref->tid, 'text' => "🤑 Реферальное вознаграждение\r\n├ Ваш доход: <b>" . $sum_currency . $ref_currency . " (" . $m_ref->ref_percent . "%)</b>\r\n└ Доступно для вывода: <b>" . $ref_balance . $ref_currency . "</b>", "parse_mode" => "HTML"]);
            }
        }
    }

    public function prepareCurlData($data,$secretKey) {

        $response = [
            "customFields" => "None",
            "expire" => $data['expire'],
            "failUrl" => $data['failUrl'],
            "hookUrl" => $data['hookUrl'],
            "includeService" => $data['includeService'],
            "orderId" => $data['orderId'],
            'shopId' => $data['shopId'],
            "successUrl" =>  $data['successUrl'],
            'sum' => $data['sum'],
        ];

        $result = $this->signature($response, $secretKey);
        return $result;
    }

    public function createInvoiceEnot($merchant_id,$amount,$currency,$order_id,$success_url,$secret_key){
        $sign = md5($merchant_id.':'.$amount.':'.$secret_key.':'.$order_id);  //Генерация ключа
        return 'https://enot.io/pay?m='.$merchant_id.'&cr='.$currency.'&oa='.$amount.'&o='.$order_id.'&s='.$sign.'&success_url='.$success_url;
    }

    public function createInvoiceFK($merchant_id, $amount, $currency, $order_id, $successUrl, $cancelUrl, $secretKey, $apiKey = null, $userIp = null, $userEmail = null, $paymentSystemId = null)
    {
         if(!empty($apiKey) && !empty($paymentSystemId)){
            $data = [
                'shopId' => (int)$merchant_id,
                'nonce' => time(),
                'i' => (int)$paymentSystemId,
                'email' => $userEmail ?? 'customer@fnrus.com',
                'ip' => $userIp ?? '127.0.0.1',
                'amount' => number_format((float)$amount, 2, '.', ''),
                'currency' => $currency,
                'paymentId' => $order_id,
            ];

            ksort($data);
            $sign = hash_hmac('sha256', implode('|', $data), $apiKey);
            $data['signature'] = $sign;

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.fk.life/v1/orders/create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            curl_close($curl);

            $result = json_decode($response, true);

            if(!empty($result['location'])){
                return $result['location'];
            }

            \Log::error('Freekassa API createOrder failed, falling back to payment form', [
                'response' => $result,
                'curl_error' => $curlError,
                'amount' => $amount,
                'order_id' => $order_id,
            ]);
        }

        $sign = md5($merchant_id.':'.$amount.':'.$secretKey.':'.$currency.':'.$order_id);
        $url = 'https://pay.fk.money/?m='.$merchant_id.'&oa='.$amount.'&o='.$order_id.'&s='.$sign.'&currency='.$currency.'&lang=ru&us='.$successUrl.'&uc='.$cancelUrl;
        if(!empty($paymentSystemId)){
            $url .= '&i='.(int)$paymentSystemId;
        }
        return $url;
    }

    public function getInvoiceBT($id, $public_key, $secret_key){

        $url = 'https://merchant.betatransfer.io/api/info?token=' . $public_key;

        $data = [
            'id' => $id,
        ];

        $data['sign'] = md5(implode('', $data) . $secret_key);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);

        curl_close($ch);

        $res = json_decode($response, true);

        dd($res);

    }

    public function createInvoiceBT($public_key, $secret_key, $amount, $currency, $method, $order_id, $successUrl, $locale)
    {

        $url = 'https://merchant.betatransfer.io/api/payment?token=' . $public_key;

        $amount_str = number_format(round($amount), 2, '.', '');
        $urlResult = config('app.url').'/pay/callback/bt';
        $urlFail = $successUrl;

        // BT requires a unique orderId per /api/payment call. The order hash is reused
        // when the user retries another payment method on /invoice/{hash}, so append a
        // short unique suffix. The original hash is preserved in user_comment and used
        // by the callback handler to look up the order.
        $unique_order_id = $order_id . '-' . substr(bin2hex(random_bytes(4)), 0, 6);

        $data = [
            'amount' => $amount_str,
            'currency' => $currency,
            'orderId' => $unique_order_id,
        ];
        if (!empty($method)) {
            $data['paymentSystem'] = $method;
        }
        $data['urlResult'] = $urlResult;
        $data['urlSuccess'] = $successUrl;
        $data['urlFail'] = $urlFail;
        $data['locale'] = mb_strtolower($locale);
        $data['user_comment'] = $order_id;
        $data['fullCallback'] = 1;

        // Signature: md5 of all POST params (in order, without sign) + secret_key
        $data['sign'] = md5(implode('', $data) . $secret_key);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);

        curl_close($ch);

        $res = json_decode($response, true);

        return $res;

    }

    public function createInvoiceRK($merchant_id,$amount,$order_id,$secretKey)
    {

        $data = [
            'shop_id'	=> $merchant_id,
            'token'		=> $secretKey,
            'order_id' 	=> $order_id,
            'amount' 	=> $amount
        ];

        $ch = curl_init('https://lk.rukassa.is/api/v1/create');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

//        dd($result);

        if (isset($result->url)) {
            if (!empty($result->url)) {
                return $result->url;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function createInvoiceLava($data,$secretKey) {
        $post = $this->prepareCurlData($data,$secretKey);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.lava.ru/business/invoice/create");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if($response['data']) {
            return $response['data']['url'];
        } else {
            return false;
        }
    }


    public function createInvoiceQiwi($amount, $currency, $order_id, $themeCode, $publicKey, $booking_time)
    {

        $curl = curl_init();

        $json = [
            "amount" => $amount,
            "currency" => $currency,
            "extras" => [
                [
                    "code" => "themeCode",
                    "value" => $themeCode
                ],
                [
                    "code" => "apiClient",
                    "value" => "p2p-admin"
                ],
                [
                    "code" => "apiClientVersion",
                    "value" => "0.17.0"
                ],
                [
                    "code" => "paySourcesFilter",
                    "value" => "card,qw,mobile"
                ]
            ],
            "customers" => [],
            "expire_date_time" => date('c', strtotime('+'.$booking_time.' minutes')),
            "comment" => $order_id,
            "public_key" => $publicKey
        ];


        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://edge.qiwi.com/checkout-api/invoice/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($json),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        $data = json_decode($response, true);

        curl_close($curl);

        return $data;
    }

    public function createInvoiceAnyPay($merchant_id, $amount, $currency, $method, $order_id, $secret_key, $locale){

        $pay_id = mt_rand(111000,9999999);

        $arr_sign = array($merchant_id, $pay_id, $amount, $currency, '', '', '', $secret_key);
        $sign = hash('sha256', implode(':', $arr_sign));
        return "https://anypay.io/merchant?merchant_id=".$merchant_id."&method=".$method."&order_id=".$order_id."&pay_id=".$pay_id."&amount=".$amount."&currency=".$currency."&sign=".$sign."&lang=".mb_strtolower($locale);

    }

    public function callback_payment($type, Request $request){
        // Diagnostic logging for all incoming callbacks
        \Log::info('PAYMENT CALLBACK RECEIVED', [
            'type' => $type,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
            'raw_content' => substr($request->getContent(), 0, 2000),
        ]);

        // CryptoBot webhook may arrive as 'wp' — remap to 'cb'
        if ($type === 'wp') {
            $type = 'cb';
        }

        $shop = Shop::getDefault();
        $sid = $shop->id;

        $shop_token = Crypt::decryptString($shop->token);

        $tg = new Api($shop_token);

        try {
            $set_shop = ShopSettings::getDefault();
            $main_currency = $set_shop->currency;
            $main_currency_symbol = $this->shop_currency[$main_currency];

            $notify_target_id = $set_shop->notify_target_id;

            $method = PaymentSystem::getByType($type);

            $date_now = Carbon::now()->timestamp;

            if($type == 'stripe'){

                $payload = $request->getContent();
                $sigHeader = $request->header('Stripe-Signature');

                try {

                    $mp = MethodPayment::getByType('sp');

                    Stripe::setApiKey($mp->secret_key);

                    $event = Event::constructFrom(
                        json_decode($payload, true),
                        $sigHeader,
                        $mp->secret_key
                    );

                } catch (\UnexpectedValueException $e) {
                    return response('Invalid payload', 400);
                } catch (\Stripe\Exception\SignatureVerificationException $e) {
                    // Signature verification failed
                    return response('Signature verification failed', 400);
                }

                if ($event->type == 'charge.succeeded') {

                    $metadata = $event->data->object->metadata;

//                    file_get_contents('https://api.telegram.org/bot6299830971:AAHfEvZ53khG2dweLqqqpL9p9LJKgzsJVZw/sendMessage?chat_id=5649294232&text=charge.succeeded / '.$metadata['order_id']);

                    $order = Order::where('sid', $sid)->where('status', 1)->where('hash', $metadata['order_id'])->first();
                    if (!$order) {throw new Exception('Заказ не найден.', 1);}

                    $member = Member::where('sid', $sid)->where('id', $order->bid)->first();
                    if (!$member) {throw new Exception('Пользователь не найден.', 1);}

                    if ($order->bid != 0) {
                        $currency_symbol = $this->shop_currency[$member->currency];
                        $currency_code = $member->currency;
                    } else {
                        $currency_symbol = $this->shop_currency[$set_shop->currency];
                        $currency_code = $set_shop->currency;
                    }

                    $product = Product::getByID($shop->id, $order->pid);

                    Product::where('id', $order->pid)->increment('count_sales', 1);

                    Material::markSoldForOrder($order->pid, $order->tid, $order->id, $order->count_all > 0 ? $order->count_all : 1);

                    Order::editByHash($order->hash, $shop->id, 'method_payment', 'ai');
                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'is_sent', 1);
                    if ($order->type != 1) { // site orders already decremented at creation
                        Product::where('id', $order->pid)->decrement('count_all', $order->count_all);
                    }

                    if($order->promo_id != 0) {
                        Coupon::where('id', $order->promo_id)->where('sid', $shop->id)->decrement('count_uses_max', 1);
                        CouponUse::create(['promo_id' => $order->promo_id, 'chat_id' => $order->bid, 'shop_id' => $shop->id, 'created_at' => $date_now]);
                    }

                    $hack_status = HackStatus::getByID($product->hack_status);

                    $status = '';
                    if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                    if($order->type == 1){$title_paid = 'Новая покупка на сайте';}
                    if($order->type == 0){$title_paid = 'Новая покупка в боте';}

                    if($member->email_notify_orders == 1 && $member->email != '') {
                        Mail::send('emails.order-receipt', ['product_title' => $product->title.' '.$status, 'product_sum' => Currency::convert($main_currency, $currency_code, $order->amount).$currency_symbol, 'order_link' => config('app.url_delivery').$order->delivery_hash], function ($message) use ($member, $title_paid) {
                            $message->to($member->email, $member->username)
                                ->subject($title_paid);
                        });
                    }

                    // Уведомление покупателю в Telegram при покупке с САЙТА,
                    // если у него привязан Telegram (вошёл через TG, есть tid).
                    if($order->type == 1 && (int)($member->tid ?? 0) > 0){
                        try {
                            $delivery_link = config('app.url_delivery') . $order->delivery_hash;
                            $cust_text = "✅ <b>Оплата получена!</b>\r\n"
                                . "├ Заказ: <code>" . $order->hash . "</code>\r\n"
                                . "└ Товар: <b>" . $product->title . " " . $status . "</b>\r\n\r\n"
                                . "Ключ и инструкция доступны по кнопке ниже.";
                            $cust_kp = json_encode(["inline_keyboard" => [[["text" => "🔑 Открыть заказ", "url" => $delivery_link]]]]);
                            $tg->sendMessage([
                                'chat_id' => $member->tid,
                                'text' => $cust_text,
                                "parse_mode" => "HTML",
                                "disable_web_page_preview" => true,
                                "reply_markup" => $cust_kp,
                            ]);
                        } catch (\Throwable $e) {
                            \Log::warning('TG buyer notify (site purchase) failed: ' . $e->getMessage());
                        }
                    }

                    if($order->type == 0){

                        $message_text = str_replace(':order_hash', $order->hash, __('bot.alert_order_paid'));

                        $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $order->id . "/get"];
                        $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $order->pid . "/1"];

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);

                        $t = Text::getByType($sid, 'after_payment');
                        if($t->is_active == 1) {
                            $message_text = str_replace(['</p><p>'], "\r\n", $t->text);
                            $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                            $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                            $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                            $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML"]);
                        }
                        Member::edit($member->id, $order->sid, 'tdata', '{}');
                        Member::edit($member->id, $order->sid, 'tstep', 0);
                    }

                    // Начисление реферальный отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    // Уведомление о новой покупке
                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новая покупка\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Товар: <b>" . $product->title . "</b>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n└ Покупатель: <b>".$member->username."</b>", "parse_mode" => "HTML"]);
                }

                return response('Webhook handled', 200);
            }

            if($type == 'bn'){

                $payload = $request->getContent();
                $data = json_decode($payload, true);
                $data_decode = json_decode($data['data'], true);

                $order_id = explode(' ', $data_decode['productName'])[1];
                $total_fee = floatval($data_decode['totalFee']);

                if($data['bizStatus'] != 'PAY_SUCCESS'){
                    throw new Exception('Ошибка.', 1);
                }

                $order = Order::where('sid', $shop->id)->where('status', 1)->where('hash', $order_id)->first();
                if (!$order) {throw new Exception('Заказ не найден.', 1);}

                if ($total_fee != floatval($order->amount)){
                    throw new Exception('Неверная сумма.', 1);
                }

                $member = Member::where('sid', $shop->id)->where('id', $order->bid)->first();
                if (!$member) {throw new Exception('Пользователь не найден.', 1);}

                if ($order->bid != 0) {
                    $currency_symbol = $this->shop_currency[$member->currency];
                    $currency_code = $member->currency;
                } else {
                    $currency_symbol = $this->shop_currency[$set_shop->currency];
                    $currency_code = $set_shop->currency;
                }

                $main_sum = Currency::convert($main_currency, $currency_code, $order->amount);

                $product = Product::getByID($shop->id, $order->pid);

                $mp = MethodPayment::getByType($type);

                $result_query = $this->checkInvoiceBinance($data_decode['merchantTradeNo'], $mp->public_key, $mp->secret_key);


                if ($order->type == 1) {
                    $title_paid = 'Новая покупка на сайте';
                    $source = 'Сайт';
                }
                if ($order->type == 0) {
                    $title_paid = 'Новая покупка в боте';
                    $source = 'Бот';
                }

                if ($result_query) {
                    if($order->pid == 0) {

                        if ($order->type == 0) {
                            $message_text = str_replace(':sum', $main_sum.$currency_symbol, __('bot.alert_balance_plus'));

                            $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];

                            $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                            if($order->msg_id > 0) {
                                $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                            }
                            $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);
                        }

                        Member::edit($member->id, $order->sid, 'tdata', '{}');
                        Member::edit($member->id, $order->sid, 'tstep', 0);
                        Order::editByHash($order->hash, $shop->id, 'status', 2);
                        Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                        Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                        Member::where('id', $member->id)->update(['balance_main' => $member->balance_main+$order->amount]);
                        
                        // Начисление реферальных отчислений
                        if($member->rid != 0){
                            $this->referralFees($tg, $member, $sid, $order, $main_currency);
                        }

                        // Уведомление о новой покупке
                        $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новое пополнение баланса\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                    } else {

                        Product::where('id', $order->pid)->increment('count_sales', 1);

                        Material::markSoldForOrder($order->pid, $order->tid, $order->id, $order->count_all > 0 ? $order->count_all : 1);

                        Order::editByHash($order->hash, $shop->id, 'method_payment', 'ai');
                        Order::editByHash($order->hash, $shop->id, 'status', 2);
                        Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                        Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                        Order::editByHash($order->hash, $shop->id, 'is_sent', 1);
                        if ($order->type != 1) { // site orders already decremented at creation
                        Product::where('id', $order->pid)->decrement('count_all', $order->count_all);
                    }

                        if ($order->promo_id != 0) {
                            Coupon::where('id', $order->promo_id)->where('sid', $shop->id)->decrement('count_uses_max', 1);
                            CouponUse::create(['promo_id' => $order->promo_id, 'chat_id' => $order->bid, 'shop_id' => $shop->id, 'created_at' => $date_now]);
                        }

                        $hack_status = HackStatus::getByID($product->hack_status);

                        $status = '';
                        if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                        if ($member->email_notify_orders == 1 && $member->email != '') {
                            Mail::send('emails.order-receipt', ['product_title' => $product->title . ' ' . $status, 'product_sum' => Currency::convert($main_currency, $currency_code, $order->amount) . $currency_symbol, 'order_link' => config('app.url_delivery') . $order->delivery_hash], function ($message) use ($member, $title_paid) {
                                $message->to($member->email, $member->username)
                                    ->subject($title_paid);
                            });
                        }

                        if ($order->type == 0) {

                            $s = Shop::getDefault();
                            $shop_token = Crypt::decryptString($s->token);

                            $tg = new Api($shop_token);

                            $message_text = str_replace(':order_hash', $order->hash, __('bot.alert_order_paid'));

                            $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $order->id . "/get"];
                            $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $order->pid . "/1"];

                            $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                            if($order->msg_id > 0) {
                                $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                            }
                            $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);

                            $t = Text::getByType($sid, 'after_payment');
                            if($t->is_active == 1) {
                                $message_text = str_replace(['</p><p>'], "\r\n", $t->text);
                                $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                                $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                                $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                                $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML"]);
                            }

                            Member::edit($member->id, $order->sid, 'tdata', '{}');
                            Member::edit($member->id, $order->sid, 'tstep', 0);
                        }

                        // Начисление реферальный отчислений
                        if($member->rid != 0){
                            $this->referralFees($tg, $member, $sid, $order, $main_currency);
                        }

                        // Уведомление о новой покупке
                        $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новая покупка\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Товар: <b>" . $product->title . "</b>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                    }



                    die('OK');

                }
                return [
                    'success' => false,
                    'message' => 'Falied',
                ];


            }



            if($type == 'pp') {
                // PayPalych / Pally postback handler
                $order_id = $request->InvId;

                $order = Order::where('sid', $shop->id)->where('status', 1)->where('hash', $order_id)->first();
                if (!$order) {throw new Exception('Заказ не найден.', 1);}

                $member = Member::where('sid', $shop->id)->where('id', $order->bid)->first();
                if (!$member) {throw new Exception('Пользователь не найден.', 1);}

                if ($order->bid != 0) {
                    $currency_symbol = $this->shop_currency[$member->currency];
                    $currency_code = $member->currency;
                } else {
                    $currency_symbol = $this->shop_currency[$set_shop->currency];
                    $currency_code = $set_shop->currency;
                }

                $main_sum = Currency::convert($main_currency, $currency_code, $order->amount);

                if($order->pid > 0) {
                    $product = Product::getByID($shop->id, $order->pid);
                }

                $mp = MethodPayment::getByType($type);

                // Проверка подписи: MD5(OutSum:InvId:SecretKey)
                $sign = strtoupper(md5($request->OutSum.':'.$request->InvId.':'.$mp->secret_key));
                if (strtoupper($request->SignatureValue) != $sign) {
                    die('wrong sign');
                }

                // Проверка статуса
                if ($request->Status != 'SUCCESS') {
                    die('not success');
                }

                if ($order->type == 1) {
                    $title_paid = 'Новая покупка на сайте';
                    $source = 'Сайт';
                }
                if ($order->type == 0) {
                    $title_paid = 'Новая покупка в боте';
                    $source = 'Бот';
                }

                if($order->pid == 0) {

                    if ($order->type == 0) {
                        $message_text = str_replace(':sum', $main_sum.$currency_symbol, __('bot.alert_balance_plus'));
                        $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];
                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);
                    }

                    Member::edit($member->id, $order->sid, 'tdata', '{}');
                    Member::edit($member->id, $order->sid, 'tstep', 0);
                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Member::where('id', $member->id)->update(['balance_main' => $member->balance_main+$order->amount]);
                    
                    // Начисление реферальных отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новое пополнение баланса
├ Номер заказа: <code>" . $order->hash . "</code>
├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>
├ Способ оплаты: <b>".$method->title."</b>
├ Покупатель: <b>".$member->username."</b>
└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                } else if($order->pid > 0) {

                    Product::where('id', $order->pid)->increment('count_sales', 1);

                    Material::markSoldForOrder($order->pid, $order->tid, $order->id, $order->count_all > 0 ? $order->count_all : 1);

                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Order::editByHash($order->hash, $shop->id, 'is_sent', 1);
                    if ($order->type != 1) { // site orders already decremented at creation
                        Product::where('id', $order->pid)->decrement('count_all', $order->count_all);
                    }

                    if ($order->promo_id != 0) {
                        Coupon::where('id', $order->promo_id)->where('sid', $shop->id)->decrement('count_uses_max', 1);
                        CouponUse::create(['promo_id' => $order->promo_id, 'chat_id' => $order->bid, 'shop_id' => $shop->id, 'created_at' => $date_now]);
                    }

                    $hack_status = HackStatus::getByID($product->hack_status);
                    $status = '';
                    if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                    if ($member->email_notify_orders == 1 && $member->email != '') {
                        Mail::send('emails.order-receipt', ['product_title' => $product->title . ' ' . $status, 'product_sum' => Currency::convert($main_currency, $currency_code, $order->amount) . $currency_symbol, 'order_link' => config('app.url_delivery') . $order->delivery_hash], function ($message) use ($member, $title_paid) {
                            $message->to($member->email, $member->username)
                                ->subject($title_paid);
                        });
                    }

                    if ($order->type == 0) {
                        $message_text = str_replace(':order_hash', $order->hash, __('bot.alert_order_paid'));
                        $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $order->id . "/get"];
                        $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $order->pid . "/1"];
                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);

                        $t = Text::getByType($sid, 'after_payment');
                        if($t->is_active == 1) {
                            $message_text = str_replace(['</p><p>'], "
", $t->text);
                            $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                            $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1
", $message_text);
                            $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));
                            $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML"]);
                        }

                        Member::edit($member->id, $order->sid, 'tdata', '{}');
                        Member::edit($member->id, $order->sid, 'tstep', 0);
                    }

                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новая покупка
├ Номер заказа: <code>" . $order->hash . "</code>
├ Товар: <b>" . $product->title . "</b>
├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>
├ Способ оплаты: <b>".$method->title."</b>
├ Покупатель: <b>".$member->username."</b>
└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);
                }

                die('OK'.$request->InvId);
            }

            if($type == 'cb') {
                // CryptoBot (Crypto Pay API) webhook handler
                $payload = $request->getContent();
                $data = json_decode($payload, true);

                // Verify webhook signature: HMAC-SHA256(body, SHA256(token))
                $mp = MethodPayment::getByType($type);
                $secret = hash('sha256', $mp->secret_key, true);
                $checkSignature = hash_hmac('sha256', $payload, $secret);
                $headerSignature = $request->header('crypto-pay-api-signature');

                if (!$headerSignature || !hash_equals($checkSignature, $headerSignature)) {
                    die('wrong sign');
                }

                // Only process invoice_paid events
                if (empty($data['update_type']) || $data['update_type'] !== 'invoice_paid') {
                    die('OK');
                }

                // Extract order hash from the invoice payload field
                $invoice = $data['payload'] ?? [];
                $order_id = $invoice['payload'] ?? null;

                if (!$order_id) {
                    die('no payload');
                }

                $order = Order::where('sid', $shop->id)->where('status', 1)->where('hash', $order_id)->first();
                if (!$order) {throw new Exception('Заказ не найден.', 1);}

                $member = Member::where('sid', $shop->id)->where('id', $order->bid)->first();
                if (!$member) {throw new Exception('Пользователь не найден.', 1);}

                if ($order->bid != 0) {
                    $currency_symbol = $this->shop_currency[$member->currency];
                    $currency_code = $member->currency;
                } else {
                    $currency_symbol = $this->shop_currency[$set_shop->currency];
                    $currency_code = $set_shop->currency;
                }

                $main_sum = Currency::convert($main_currency, $currency_code, $order->amount);

                if($order->pid > 0) {
                    $product = Product::getByID($shop->id, $order->pid);
                }

                if ($order->type == 1) {
                    $title_paid = 'Новая покупка на сайте';
                    $source = 'Сайт';
                }
                if ($order->type == 0) {
                    $title_paid = 'Новая покупка в боте';
                    $source = 'Бот';
                }

                if($order->pid == 0) {
                    // Balance top-up
                    if ($order->type == 0) {
                        $message_text = str_replace(':sum', $main_sum.$currency_symbol, __('bot.alert_balance_plus'));
                        $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];
                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);
                    }

                    Member::edit($member->id, $order->sid, 'tdata', '{}');
                    Member::edit($member->id, $order->sid, 'tstep', 0);
                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Member::where('id', $member->id)->update(['balance_main' => $member->balance_main+$order->amount]);
                    
                    // Начисление реферальных отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новое пополнение баланса\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                } else if($order->pid > 0) {
                    // Product purchase
                    Product::where('id', $order->pid)->increment('count_sales', 1);

                    Material::markSoldForOrder($order->pid, $order->tid, $order->id, $order->count_all > 0 ? $order->count_all : 1);

                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Order::editByHash($order->hash, $shop->id, 'is_sent', 1);
                    if ($order->type != 1) { // site orders already decremented at creation
                        Product::where('id', $order->pid)->decrement('count_all', $order->count_all);
                    }

                    if ($order->promo_id != 0) {
                        Coupon::where('id', $order->promo_id)->where('sid', $shop->id)->decrement('count_uses_max', 1);
                        CouponUse::create(['promo_id' => $order->promo_id, 'chat_id' => $order->bid, 'shop_id' => $shop->id, 'created_at' => $date_now]);
                    }

                    $hack_status = HackStatus::getByID($product->hack_status);
                    $status = '';
                    if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                    if ($member->email_notify_orders == 1 && $member->email != '') {
                        Mail::send('emails.order-receipt', ['product_title' => $product->title . ' ' . $status, 'product_sum' => Currency::convert($main_currency, $currency_code, $order->amount) . $currency_symbol, 'order_link' => config('app.url_delivery') . $order->delivery_hash], function ($message) use ($member, $title_paid) {
                            $message->to($member->email, $member->username)
                                ->subject($title_paid);
                        });
                    }

                    if ($order->type == 0) {
                        $message_text = str_replace(':order_hash', $order->hash, __('bot.alert_order_paid'));
                        $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $order->id . "/get"];
                        $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $order->pid . "/1"];
                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);

                        $t = Text::getByType($sid, 'after_payment');
                        if($t->is_active == 1) {
                            $message_text = str_replace(['</p><p>'], "\r\n", $t->text);
                            $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                            $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                            $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));
                            $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML"]);
                        }

                        Member::edit($member->id, $order->sid, 'tdata', '{}');
                        Member::edit($member->id, $order->sid, 'tstep', 0);
                    }

                    // Начисление реферальных отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    // Уведомление о новой покупке
                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новая покупка\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Товар: <b>" . $product->title . "</b>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);
                }

                die('OK');
            }

            if($type == 'sm') {
                // StreamPay callback handler
                $payload = $request->getContent();
                $data = json_decode($payload, true);

                $mp = MethodPayment::getByType($type);

                // Verify signature: HMAC-SHA256(payload, secret_key)
                $headerSignature = $request->header('X-Signature') ?? $request->header('Signature') ?? '';
                $checkSignature = hash_hmac('sha256', $payload, $mp->secret_key);

                if (!empty($headerSignature) && !hash_equals($checkSignature, $headerSignature)) {
                    die('wrong sign');
                }

                // Extract order_id from callback data
                $order_id = $data['order_id'] ?? ($data['data']['order_id'] ?? null);
                $status_pay = $data['status'] ?? ($data['data']['status'] ?? '');

                if (!$order_id) {
                    die('no order_id');
                }

                // Only process successful payments
                if (!in_array(strtolower($status_pay), ['success', 'paid', 'completed'])) {
                    die('OK');
                }

                $order = Order::where('sid', $shop->id)->where('status', 1)->where('hash', $order_id)->first();
                if (!$order) {throw new Exception('Заказ не найден.', 1);}

                $member = Member::where('sid', $shop->id)->where('id', $order->bid)->first();
                if (!$member) {throw new Exception('Пользователь не найден.', 1);}

                if ($order->bid != 0) {
                    $currency_symbol = $this->shop_currency[$member->currency];
                    $currency_code = $member->currency;
                } else {
                    $currency_symbol = $this->shop_currency[$set_shop->currency];
                    $currency_code = $set_shop->currency;
                }

                $main_sum = Currency::convert($main_currency, $currency_code, $order->amount);

                if($order->pid > 0) {
                    $product = Product::getByID($shop->id, $order->pid);
                }

                if ($order->type == 1) {
                    $title_paid = 'Новая покупка на сайте';
                    $source = 'Сайт';
                }
                if ($order->type == 0) {
                    $title_paid = 'Новая покупка в боте';
                    $source = 'Бот';
                }

                if($order->pid == 0) {
                    // Balance top-up
                    if ($order->type == 0) {
                        $message_text = str_replace(':sum', $main_sum.$currency_symbol, __('bot.alert_balance_plus'));
                        $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];
                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);
                    }

                    Member::edit($member->id, $order->sid, 'tdata', '{}');
                    Member::edit($member->id, $order->sid, 'tstep', 0);
                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Member::where('id', $member->id)->update(['balance_main' => $member->balance_main+$order->amount]);
                    
                    // Начисление реферальных отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новое пополнение баланса\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                } else if($order->pid > 0) {
                    // Product purchase
                    Product::where('id', $order->pid)->increment('count_sales', 1);

                    Material::markSoldForOrder($order->pid, $order->tid, $order->id, $order->count_all > 0 ? $order->count_all : 1);

                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Order::editByHash($order->hash, $shop->id, 'is_sent', 1);
                    if ($order->type != 1) { // site orders already decremented at creation
                        Product::where('id', $order->pid)->decrement('count_all', $order->count_all);
                    }

                    if ($order->promo_id != 0) {
                        Coupon::where('id', $order->promo_id)->where('sid', $shop->id)->decrement('count_uses_max', 1);
                        CouponUse::create(['promo_id' => $order->promo_id, 'chat_id' => $order->bid, 'shop_id' => $shop->id, 'created_at' => $date_now]);
                    }

                    $hack_status = HackStatus::getByID($product->hack_status);
                    $status = '';
                    if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                    if ($member->email_notify_orders == 1 && $member->email != '') {
                        Mail::send('emails.order-receipt', ['product_title' => $product->title . ' ' . $status, 'product_sum' => Currency::convert($main_currency, $currency_code, $order->amount) . $currency_symbol, 'order_link' => config('app.url_delivery') . $order->delivery_hash], function ($message) use ($member, $title_paid) {
                            $message->to($member->email, $member->username)
                                ->subject($title_paid);
                        });
                    }

                    if ($order->type == 0) {
                        $message_text = str_replace(':order_hash', $order->hash, __('bot.alert_order_paid'));
                        $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $order->id . "/get"];
                        $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $order->pid . "/1"];
                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);

                        $t = Text::getByType($sid, 'after_payment');
                        if($t->is_active == 1) {
                            $message_text = str_replace(['</p><p>'], "\r\n", $t->text);
                            $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                            $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                            $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));
                            $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML"]);
                        }

                        Member::edit($member->id, $order->sid, 'tdata', '{}');
                        Member::edit($member->id, $order->sid, 'tstep', 0);
                    }

                    // Начисление реферальных отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    // Уведомление о новой покупке
                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новая покупка\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Товар: <b>" . $product->title . "</b>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);
                }

                die('OK');
            }

            if($type == 'fk') {
                // Проверка IP Freekassa (учитываем Mitelis CDN proxy)
                $fk_ips = ['168.119.157.136', '168.119.60.227', '178.154.197.79', '51.250.54.238'];
                $clientIp = $request->ip();

                // За Mitelis CDN request->ip() может вернуть IP прокси, поэтому проверяем X-Forwarded-For
                $xff = $request->header('X-Forwarded-For');
                if ($xff) {
                    $ips = array_map('trim', explode(',', $xff));
                    $clientIp = $ips[0]; // Первый IP — оригинальный клиент
                }

                if (!in_array($clientIp, $fk_ips)) {
                    \Log::warning('FK callback rejected: IP not in whitelist', [
                        'client_ip' => $clientIp,
                        'request_ip' => $request->ip(),
                        'x_forwarded_for' => $xff,
                        'allowed_ips' => $fk_ips,
                        'all_data' => $request->all(),
                    ]);
                    die('IP not allowed');
                }

                $order_id = $request->MERCHANT_ORDER_ID;

                $order = Order::where('sid', $shop->id)->where('status', 1)->where('hash', $order_id)->first();
                if (!$order) {throw new Exception('Заказ не найден.', 1);}

                $member = Member::where('sid', $shop->id)->where('id', $order->bid)->first();
                if (!$member) {throw new Exception('Пользователь не найден.', 1);}

                if ($order->bid != 0) {
                    $currency_symbol = $this->shop_currency[$member->currency];
                    $currency_code = $member->currency;
                } else {
                    $currency_symbol = $this->shop_currency[$set_shop->currency];
                    $currency_code = $set_shop->currency;
                }

                $main_sum = Currency::convert($main_currency, $currency_code, $order->amount);

                if($order->pid > 0) {
                    $product = Product::getByID($shop->id, $order->pid);
                }

                $mp = MethodPayment::getByType($type);

                // Проверка подписи (Secret Word 2)
                $sign = md5($mp->public_id.':'.$request->AMOUNT.':'.$mp->secret_key_two.':'.$request->MERCHANT_ORDER_ID);
                if ($sign != $request->SIGN) {
                    \Log::warning('FK callback rejected: wrong sign', [
                        'expected' => $sign,
                        'received' => $request->SIGN,
                        'public_id' => $mp->public_id,
                        'amount' => $request->AMOUNT,
                        'order_id' => $request->MERCHANT_ORDER_ID,
                    ]);
                    die('wrong sign');
                }

                if ($order->type == 1) {
                    $title_paid = 'Новая покупка на сайте';
                    $source = 'Сайт';
                }
                if ($order->type == 0) {
                    $title_paid = 'Новая покупка в боте';
                    $source = 'Бот';
                }

                if($order->pid == 0) {

                    if ($order->type == 0) {
                        $message_text = str_replace(':sum', $main_sum.$currency_symbol, __('bot.alert_balance_plus'));

                        $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);
                    }

                    Member::edit($member->id, $order->sid, 'tdata', '{}');
                    Member::edit($member->id, $order->sid, 'tstep', 0);
                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Member::where('id', $member->id)->update(['balance_main' => $member->balance_main+$order->amount]);
                    
                    // Начисление реферальных отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    // Уведомление о новом пополнении
                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новое пополнение баланса\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                } else if($order->pid > 0) {

                    Product::where('id', $order->pid)->increment('count_sales', 1);

                    Material::markSoldForOrder($order->pid, $order->tid, $order->id, $order->count_all > 0 ? $order->count_all : 1);

                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Order::editByHash($order->hash, $shop->id, 'is_sent', 1);
                    if ($order->type != 1) { // site orders already decremented at creation
                        Product::where('id', $order->pid)->decrement('count_all', $order->count_all);
                    }

                    if ($order->promo_id != 0) {
                        Coupon::where('id', $order->promo_id)->where('sid', $shop->id)->decrement('count_uses_max', 1);
                        CouponUse::create(['promo_id' => $order->promo_id, 'chat_id' => $order->bid, 'shop_id' => $shop->id, 'created_at' => $date_now]);
                    }

                    $hack_status = HackStatus::getByID($product->hack_status);

                    $status = '';
                    if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                    if ($member->email_notify_orders == 1 && $member->email != '') {
                        Mail::send('emails.order-receipt', ['product_title' => $product->title . ' ' . $status, 'product_sum' => Currency::convert($main_currency, $currency_code, $order->amount) . $currency_symbol, 'order_link' => config('app.url_delivery') . $order->delivery_hash], function ($message) use ($member, $title_paid) {
                            $message->to($member->email, $member->username)
                                ->subject($title_paid);
                        });
                    }

                    if ($order->type == 0) {

                        $message_text = str_replace(':order_hash', $order->hash, __('bot.alert_order_paid'));

                        $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $order->id . "/get"];
                        $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $order->pid . "/1"];

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);

                        $t = Text::getByType($sid, 'after_payment');
                        if($t->is_active == 1) {
                            $message_text = str_replace(['</p><p>'], "\r\n", $t->text);
                            $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                            $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "\$1\r\n", $message_text);
                            $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                            $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML"]);
                        }

                        Member::edit($member->id, $order->sid, 'tdata', '{}');
                        Member::edit($member->id, $order->sid, 'tstep', 0);
                    }

                    // Начисление реферальных отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    // Уведомление о новой покупке
                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новая покупка\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Товар: <b>" . $product->title . "</b>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                }

                die('YES');
            }

            if($type == 'ai' || $type == 'ap' || $type == 'cp' || $type == 'bt') {
                if($type == 'ai'){$order_id = $request->order_id;}
                if($type == 'ap'){$order_id = $request->order_id;}
                if($type == 'cp'){$order_id = $request->extra;}
                if($type == 'bt'){$order_id = $request->user_comment;}

                $order = Order::where('sid', $shop->id)->where('status', 1)->where('hash', $order_id)->first();
                if (!$order) {throw new Exception('Заказ не найден.', 1);}

                $member = Member::where('sid', $shop->id)->where('id', $order->bid)->first();
                if (!$member) {throw new Exception('Пользователь не найден.', 1);}

                if ($order->bid != 0) {
                    $currency_symbol = $this->shop_currency[$member->currency];
                    $currency_code = $member->currency;
                } else {
                    $currency_symbol = $this->shop_currency[$set_shop->currency];
                    $currency_code = $set_shop->currency;
                }

                $main_sum = Currency::convert($main_currency, $currency_code, $order->amount);

                if($order->pid > 0) {
                    $product = Product::getByID($shop->id, $order->pid);
                }

                $mp = MethodPayment::getByType($type);

                if($type == 'ap'){

                    $secret = $mp->secret_key;

                    $arr_sign = array(
                        $request->currency,
                        $request->amount,
                        $request->pay_id,
                        $mp->public_id,
                        'paid',
                        $secret
                    );

                    $sign = hash('sha256', implode(":", $arr_sign));

                    if($sign != $_REQUEST['sign']){
                        die('wrong sign!');
                    }
                }

                if($type == 'ai') {
                    $secret = $mp->secret_key_two;

                    $sign = hash('sha256', implode(':', [$request->merchant_id, $request->amount, $request->currency, $secret, $order_id]));

                    if (!hash_equals($request->sign, $sign)) {
                        die("wrong sign");
                    }
                }

                if($type == 'cp'){

                    if($request->state != 'payed'){
                        exit('Not paid');
                    }

                    $hash = sha1($request->id . ":" . $mp->secret_key_two);

                    if (!hash_equals($hash, $request->signature)) {
                        exit("Invalid signature!");
                    }
                }

                if($type == 'bt'){

                    if($request->status != 'success'){
                        exit('Not paid');
                    }

                    $sign = md5($request->amount . $request->orderId . $mp->secret_key);

                    if ($sign != $request->sign) {
                        exit("Invalid signature!");
                    }
                }

                if ($order->type == 1) {
                    $title_paid = 'Новая покупка на сайте';
                    $source = 'Сайт';
                }
                if ($order->type == 0) {
                    $title_paid = 'Новая покупка в боте';
                    $source = 'Бот';
                }

                if($order->pid == 0) {

                    if ($order->type == 0) {
                        $message_text = str_replace(':sum', $main_sum.$currency_symbol, __('bot.alert_balance_plus'));

                        $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);
                    }

                    Member::edit($member->id, $order->sid, 'tdata', '{}');
                    Member::edit($member->id, $order->sid, 'tstep', 0);
                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Member::where('id', $member->id)->update(['balance_main' => $member->balance_main+$order->amount]);
                    
                    // Начисление реферальных отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    // Уведомление о новой покупке
                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новое пополнение баланса\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                } else if($order->pid > 0) {

                    Product::where('id', $order->pid)->increment('count_sales', 1);

                    Material::markSoldForOrder($order->pid, $order->tid, $order->id, $order->count_all > 0 ? $order->count_all : 1);

                    Order::editByHash($order->hash, $shop->id, 'method_payment', '');
                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Order::editByHash($order->hash, $shop->id, 'is_sent', 1);
                    if ($order->type != 1) { // site orders already decremented at creation
                        Product::where('id', $order->pid)->decrement('count_all', $order->count_all);
                    }

                    if ($order->promo_id != 0) {
                        Coupon::where('id', $order->promo_id)->where('sid', $shop->id)->decrement('count_uses_max', 1);
                        CouponUse::create(['promo_id' => $order->promo_id, 'chat_id' => $order->bid, 'shop_id' => $shop->id, 'created_at' => $date_now]);
                    }

                    $hack_status = HackStatus::getByID($product->hack_status);

                    $status = '';
                    if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                    if ($member->email_notify_orders == 1 && $member->email != '') {
                        Mail::send('emails.order-receipt', ['product_title' => $product->title . ' ' . $status, 'product_sum' => Currency::convert($main_currency, $currency_code, $order->amount) . $currency_symbol, 'order_link' => config('app.url_delivery') . $order->delivery_hash], function ($message) use ($member, $title_paid) {
                            $message->to($member->email, $member->username)
                                ->subject($title_paid);
                        });
                    }

                    if ($order->type == 0) {

                        $message_text = str_replace(':order_hash', $order->hash, __('bot.alert_order_paid'));

                        $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $order->id . "/get"];
                        $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $order->pid . "/1"];

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                        if($order->msg_id > 0) {
                            $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                        }
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);

                        $t = Text::getByType($sid, 'after_payment');
                        if($t->is_active == 1) {
                            $message_text = str_replace(['</p><p>'], "\r\n", $t->text);
                            $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                            $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                            $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                            $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML"]);
                        }

                        Member::edit($member->id, $order->sid, 'tdata', '{}');
                        Member::edit($member->id, $order->sid, 'tstep', 0);

                    }

                    // Начисление реферальный отчислений
                    if($member->rid != 0){
                        $this->referralFees($tg, $member, $sid, $order, $main_currency);
                    }

                    // Уведомление о новой покупке
                    $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новая покупка\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Товар: <b>" . $product->title . "</b>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>".$method->title."</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                }

                die('OK');
            }

        } catch (Exception $e) {
            $tg->sendMessage(['chat_id' => 5649294232, 'text' => $type.' / '.$e->getMessage(), "parse_mode" => "HTML"]);
        }
    }


    public function btkassa(){

        $mp = MethodPayment::where('type', 'bt')->first();

        $public = $mp->public_key;
        $secret = $mp->secret_key;

        $url = 'https://merchant.betatransfer.io/api/history?token=' . $public;

        $data = [
            'type' => 'deposit',
        ];

        $data['sign'] = md5(implode('', $data) . $secret);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($response, true);

        return response()->json($response);

    }

    public function writeEventToTxt($event, $filename) {
        $eventJson = json_encode($event);

        file_put_contents($filename, $eventJson);
    }

    public function convertToCents($amount)
    {
        // Уберем знаки препинания и преобразуем в целое число
        $cleanedAmount = str_replace(['$', ',', '.'], '', $amount);

        // Умножим сумму на 100, чтобы получить центы
        $cents = $amount * 100;

        return $cents;
    }

    /**
     * Telegram Bot webhook handler for Stars payments.
     * Handles pre_checkout_query (approve) and successful_payment (process order).
     */
    public function telegramWebhook(Request $request){

        $payload = $request->getContent();
        $update = json_decode($payload, true);

        if (empty($update)) {
            return response('empty', 400);
        }

        // Verify request comes from Telegram using secret_token header
        // Accept tokens from both the notify bot and the Stars payment bot
        $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        $starsMethod = MethodPayment::getByType('ts');
        $validTokens = [
            hash('sha256', config('app.bot_telegram_notify_token')),
        ];
        if ($starsMethod && !empty($starsMethod->secret_key)) {
            $validTokens[] = hash('sha256', $starsMethod->secret_key);
        }
        if (!in_array($headerToken, $validTokens, true)) {
            \Log::warning('Telegram webhook: invalid secret token', ['header' => $headerToken]);
            return response('forbidden', 403);
        }

        // Handle pre_checkout_query — MUST respond within 10 seconds
        if (!empty($update['pre_checkout_query'])) {
            $query = $update['pre_checkout_query'];
            $bot_token = ($starsMethod && !empty($starsMethod->secret_key)) ? $starsMethod->secret_key : config('app.bot_telegram_notify_token');

            // Verify the order exists and is still pending
            $order_hash = $query['invoice_payload'] ?? null;
            $order = null;
            if ($order_hash) {
                $shop = Shop::getDefault();
                $order = Order::where('sid', $shop->id)->where('status', 1)->where('hash', $order_hash)->first();
            }

            $ok = $order ? true : false;
            $error_message = $order ? '' : 'Заказ не найден или уже оплачен';

            $body = [
                'pre_checkout_query_id' => $query['id'],
                'ok' => $ok,
            ];
            if (!$ok) {
                $body['error_message'] = $error_message;
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.telegram.org/bot' . $bot_token . '/answerPreCheckoutQuery',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            \Log::info('Telegram Stars pre_checkout_query answered', [
                'query_id' => $query['id'],
                'order_hash' => $order_hash,
                'ok' => $ok,
                'response' => $result,
            ]);

            return response('OK');
        }

        // Handle successful_payment — process the order
        if (!empty($update['message']['successful_payment'])) {
            $payment = $update['message']['successful_payment'];
            $order_hash = $payment['invoice_payload'] ?? null;

            if (!$order_hash) {
                \Log::error('Telegram Stars successful_payment: no payload');
                return response('no payload', 400);
            }

            $shop = Shop::getDefault();
            $sid = $shop->id;

            $shop_token = \Crypt::decryptString($shop->token);
            $tg = new \Telegram\Bot\Api($shop_token);

            $set_shop = ShopSettings::getDefault();
            $main_currency = $set_shop->currency;
            $main_currency_symbol = $this->shop_currency[$main_currency];
            $notify_target_id = $set_shop->notify_target_id;

            $method = PaymentSystem::getByType('ts');
            $date_now = \Carbon\Carbon::now()->timestamp;

            $order = Order::where('sid', $shop->id)->where('status', 1)->where('hash', $order_hash)->first();
            if (!$order) {
                \Log::warning('Telegram Stars: order not found or already paid', ['hash' => $order_hash]);
                return response('order not found');
            }

            $member = Member::where('sid', $shop->id)->where('id', $order->bid)->first();
            if (!$member) {
                \Log::error('Telegram Stars: member not found', ['bid' => $order->bid]);
                return response('member not found');
            }

            if ($order->bid != 0) {
                $currency_symbol = $this->shop_currency[$member->currency];
                $currency_code = $member->currency;
            } else {
                $currency_symbol = $this->shop_currency[$set_shop->currency];
                $currency_code = $set_shop->currency;
            }

            $main_sum = Currency::convert($main_currency, $currency_code, $order->amount);

            if($order->pid > 0) {
                $product = Product::getByID($shop->id, $order->pid);
            }

            if ($order->type == 1) {
                $title_paid = 'Новая покупка на сайте';
                $source = 'Сайт';
            }
            if ($order->type == 0) {
                $title_paid = 'Новая покупка в боте';
                $source = 'Бот';
            }

            $type = 'ts';

            if($order->pid == 0) {
                // Balance top-up
                if ($order->type == 0) {
                    $message_text = str_replace(':sum', $main_sum.$currency_symbol, __('bot.alert_balance_plus'));
                    $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];
                    $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                    if($order->msg_id > 0) {
                        $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                    }
                    $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);
                }

                Member::edit($member->id, $order->sid, 'tdata', '{}');
                Member::edit($member->id, $order->sid, 'tstep', 0);
                Order::editByHash($order->hash, $shop->id, 'status', 2);
                Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                Member::where('id', $member->id)->update(['balance_main' => $member->balance_main+$order->amount]);
                
                // Начисление реферальных отчислений
                if($member->rid != 0){
                    $this->referralFees($tg, $member, $sid, $order, $main_currency);
                }

                $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "⭐ Новое пополнение баланса (Stars)\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Stars: <b>" . $payment['total_amount'] . " ⭐</b>\r\n├ Способ оплаты: <b>Telegram Stars</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

            } else if($order->pid > 0) {
                // Product purchase
                Product::where('id', $order->pid)->increment('count_sales', 1);

                Material::markSoldForOrder($order->pid, $order->tid, $order->id, $order->count_all > 0 ? $order->count_all : 1);

                    Order::editByHash($order->hash, $shop->id, 'status', 2);
                    Order::editByHash($order->hash, $shop->id, 'payment_at', $date_now);
                    Order::editByHash($order->hash, $shop->id, 'method_payment', $type);
                    Order::editByHash($order->hash, $shop->id, 'is_sent', 1);
                    if ($order->type != 1) { // site orders already decremented at creation
                        Product::where('id', $order->pid)->decrement('count_all', $order->count_all);
                    }

                    if ($order->promo_id != 0) {
                        Coupon::where('id', $order->promo_id)->where('sid', $shop->id)->decrement('count_uses_max', 1);
                        CouponUse::create(['promo_id' => $order->promo_id, 'chat_id' => $order->bid, 'shop_id' => $shop->id, 'created_at' => $date_now]);
                    }

                $hack_status = HackStatus::getByID($product->hack_status);
                $status = '';
                if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                if ($member->email_notify_orders == 1 && $member->email != '') {
                    \Mail::send('emails.order-receipt', ['product_title' => $product->title . ' ' . $status, 'product_sum' => Currency::convert($main_currency, $currency_code, $order->amount) . $currency_symbol, 'order_link' => config('app.url_delivery') . $order->delivery_hash], function ($message) use ($member, $title_paid) {
                        $message->to($member->email, $member->username)
                            ->subject($title_paid);
                    });
                }

                if ($order->type == 0) {
                    $message_text = str_replace(':order_hash', $order->hash, __('bot.alert_order_paid'));
                    $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $order->id . "/get"];
                    $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $order->pid . "/1"];
                    $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                    if($order->msg_id > 0) {
                        $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $order->msg_id + 1]);
                    }
                    $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => false, "reply_markup" => $kp]);

                    $t = Text::getByType($sid, 'after_payment');
                    if($t->is_active == 1) {
                        $message_text = str_replace(['</p><p>'], "\r\n", $t->text);
                        $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                        $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                        $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));
                        $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML"]);
                    }

                    Member::edit($member->id, $order->sid, 'tdata', '{}');
                    Member::edit($member->id, $order->sid, 'tstep', 0);
                }

                // Начисление реферальных отчислений
                if($member->rid != 0){
                    $this->referralFees($tg, $member, $sid, $order, $main_currency);
                }

                // Уведомление о новой покупке
                $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "⭐ Новая покупка (Stars)\r\n├ Номер заказа: <code>" . $order->hash . "</code>\r\n├ Товар: <b>" . $product->title . "</b>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Stars: <b>" . $payment['total_amount'] . " ⭐</b>\r\n├ Способ оплаты: <b>Telegram Stars</b>\r\n├ Покупатель: <b>".$member->username."</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);
            }

            \Log::info('Telegram Stars payment processed', [
                'order_hash' => $order_hash,
                'stars_amount' => $payment['total_amount'],
                'telegram_charge_id' => $payment['telegram_payment_charge_id'] ?? null,
            ]);

            return response('OK');
        }

        // Unknown update type — log and ignore
        \Log::info('Telegram webhook: unhandled update type', ['update_id' => $update['update_id'] ?? null]);
        return response('OK');
    }

}
