<?php

namespace App\Models;

use Exception;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Telegram\Bot\Api;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'orders';
    protected $fillable = [
        'id',
        'sid',
        'pid',
        'tid',
        'bid',
        'oid',
        'title',
        'amount',
        'currency',
        'amount_rub',
        'count_all',
        'promo_id',
        'is_sent',
        'msg_id',
        'method_payment',
        'status',
        'hash',
        'delivery_hash',
        'type',
        'payment_at',
        'expired_at',
        'created_at'
    ];

    public static function getCountAll(){
        return Order::count();
    }

    public static function getByID($sid, $id){
        return Order::where('sid', $sid)->where('id', $id)->first();
    }

    public static function changeStatusByExpiredAt(){

        try {

            $shop = Shop::getDefault();
            $shop_token = Crypt::decryptString($shop->token);

            $tg = new Api($shop_token);

            $expired_at = strtotime(date('Y-m-d H:i'));

            $results = Order::where('expired_at', '<=', $expired_at)->where('expired_at', '>', 0)->where('status', 1)->get();

            foreach($results as $result){

                $member = Member::getByID($result->bid, $shop->id);

                if($member && $member->tid != 0) {

                    $ki[$result->id][] = ["text" => __('bot.btn_back_to_product'), "callback_data" => "products/" . $result->pid . "/1"];
                    $kp = json_encode(["inline_keyboard" => array_chunk($ki[$result->id], 1)]);

                    $message_text = str_replace(':order_hash', $result->hash, __('bot.alert_order_time_canceled'));
                    $res = $tg->sendMessage(['chat_id' => $member->tid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);

                    if($result->msg_id != 0) {
                        $tg->deleteMessage(['chat_id' => $member->tid, 'message_id' => $res->message_id - 1]);
                    }
                }

                Order::where('id', $result->id)->update(['status' => 4]);
                if($result->pid > 0) {
                    Product::where('id', $result->pid)->increment('count_all', $result->count_all);
                }
                Material::releaseFromOrder($result->id);
            }


        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    public static function getByHash($sid, $hash){
        return Order::where('sid', $sid)->where('hash', $hash)->first();
    }

    public static function getByHashNoAuth($hash){
        return Order::where('delivery_hash', $hash)->first();
    }
    public static function getCountByCid($cid, $sid){
        return Order::where('bid', $cid)
            ->where('sid', $sid)
            ->count();
    }
    public static function getMaterialsByID($cid, $sid, $id){
        $order = Order::where('sid', $sid)
            ->where('bid', $cid)
            ->where('id', $id)
            ->first();

        $materials = Material::where('sid', $sid)
            ->where('oid', $order->id)
            ->where('status', 2)
            ->get();

        $body = [];

        foreach ($materials as $m) {
            $body[] = $m->body;
        }

        return implode("\n", $body);
    }

    public static function getMaterialsByIDNoAuth($id){
        $order = Order::where('id', $id)->first();
        $materials = Material::where('oid', $order->id)->where('status', 2)->get();

        $body = [];

        foreach ($materials as $m) {
            $body[] = $m->body;
        }

        return implode("\n", $body);
    }

    public static function editByHash($hash, $sid, $key, $value){
        return Order::where('sid', $sid)->where('hash', $hash)->update([$key => $value]);
    }

    public static function editById($id, $sid, $key, $value){
        return Order::where('sid', $sid)->where('id', $id)->update([$key => $value]);
    }
    public static function getListByPage($cid, $sid, $page, $limit){
        $startIndex = ($page - 1) * $limit;
        $allItems = Order::where('bid', $cid)
            ->where('sid', $sid)
            ->where('type', 0)
            ->orderBy('id', 'desc')
            ->offset($startIndex)
            ->limit($limit)
            ->get();

        return $allItems;
    }
}

