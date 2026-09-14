<?php

namespace App\Models;

use Exception;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class Order extends Model
{
    use HasFactory;

    /**
     * A pending checkout must never reserve stock indefinitely. Normal orders
     * expire by expired_at; this fallback also closes legacy/broken rows whose
     * expiry timestamp was never written correctly.
     */
    public const MAX_PENDING_AGE = 86400;

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

    public static function changeStatusByExpiredAt(): Collection
    {
        $expired = self::expirePending();

        // Telegram is only a notification channel. A broken/missing token or
        // Telegram outage must never keep an order and its stock reserved.
        if ($expired->isEmpty()) return $expired;

        try {
            $shop = Shop::getDefault();
            if (!$shop || !$shop->token) return $expired;

            $tg = new Api(Crypt::decryptString($shop->token));

            foreach ($expired as $result) {
                try {
                    $member = Member::getByID($result->bid, $result->sid);
                    if (!$member || !$member->tid) continue;

                    $keyboard = json_encode(['inline_keyboard' => [[
                        ['text' => __('bot.btn_back_to_product'), 'callback_data' => 'products/' . $result->pid . '/1'],
                    ]]]);
                    $message = str_replace(':order_hash', $result->hash, __('bot.alert_order_time_canceled'));
                    $tg->sendMessage([
                        'chat_id' => $member->tid,
                        'text' => $message,
                        'parse_mode' => 'HTML',
                        'reply_markup' => $keyboard,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Expired-order Telegram notification failed', [
                        'order_id' => $result->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Expired-order Telegram setup failed', ['error' => $e->getMessage()]);
        }

        return $expired;
    }

    /**
     * Expire all overdue unpaid orders atomically and return their stock.
     * Safe to call from cron and from ordinary requests; the conditional
     * update guarantees that stock is restored exactly once.
     */
    public static function expirePending(?int $buyerId = null)
    {
        $now = time();
        $staleCreatedAt = $now - self::MAX_PENDING_AGE;

        $query = self::query()
            ->where('status', 1)
            ->where(function ($query) use ($now, $staleCreatedAt) {
                $query->where(function ($expired) use ($now) {
                    $expired->where('expired_at', '>', 0)
                        ->where('expired_at', '<=', $now);
                })->orWhere(function ($stale) use ($staleCreatedAt) {
                    $stale->where('created_at', '>', 0)
                        ->where('created_at', '<=', $staleCreatedAt);
                });
            });

        if ($buyerId !== null) $query->where('bid', $buyerId);

        $ids = $query->orderBy('id')->pluck('id');
        $expired = collect();

        foreach ($ids as $id) {
            $order = self::expirePendingById((int) $id);

            if ($order) $expired->push($order);
        }

        return $expired;
    }

    /**
     * Expire one overdue unpaid order and release its reservation exactly once.
     */
    public static function expirePendingById(int $id): ?self
    {
        return DB::transaction(function () use ($id) {
            $order = self::whereKey($id)->lockForUpdate()->first();
            if (!$order
                || (int) $order->status !== 1
                || !self::isPendingExpired($order)) {
                return null;
            }

            $changed = self::whereKey($id)
                ->where('status', 1)
                ->update(['status' => 4]);
            if (!$changed) return null;

            if ((int) $order->pid > 0 && (int) $order->count_all > 0) {
                Product::whereKey($order->pid)->increment('count_all', (int) $order->count_all);
            }
            Material::releaseFromOrder((int) $order->id);

            $order->status = 4;
            return $order;
        });
    }

    public static function isPendingExpired(self $order, ?int $now = null): bool
    {
        $now = $now ?? time();
        $expiredAt = (int) $order->expired_at;
        $createdAt = (int) $order->created_at;

        return ($expiredAt > 0 && $expiredAt <= $now)
            || ($createdAt > 0 && $createdAt <= $now - self::MAX_PENDING_AGE);
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
