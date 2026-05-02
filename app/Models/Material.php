<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Material extends Model
{

    public $timestamps = false;
    protected $table = 'materials';
    protected $fillable = ['id','sid','pid','tid','eid','oid','bid','body','status','created_at'];

    public static function getCountSales($tid, $pid){
        return Material::where('tid', $tid)->where('pid', $pid)->where('status', 2)->count();
    }

    public static function getCountAll($tid, $pid){
        return Material::where('tid', $tid)->where('pid', $pid)->where('status', 1)->count();
    }

    public static function getCountAllByTID($tid){
        return Material::where('tid', $tid)->where('status', 1)->count();
    }

    /**
     * Reserve up to $count available materials for an order.
     * Sets status=4 (reserved), oid=order_id, bid=buyer_id.
     * Returns the number of materials actually reserved.
     */
    public static function reserveForOrder($pid, $tid, $oid, $bid, $count){
        if ($count <= 0) { return 0; }
        $items = Material::where('pid', $pid)
            ->where('tid', $tid)
            ->where('status', 1)
            ->limit($count)
            ->get();
        $reserved = 0;
        foreach ($items as $item) {
            $affected = Material::where('id', $item->id)
                ->where('status', 1)
                ->update(['status' => 4, 'oid' => $oid, 'bid' => $bid]);
            if ($affected) { $reserved++; }
        }
        return $reserved;
    }

    /**
     * Release all materials reserved for the given order back to status=1.
     */
    public static function releaseFromOrder($oid){
        if ($oid <= 0) { return 0; }
        return Material::where('oid', $oid)
            ->where('status', 4)
            ->update(['status' => 1, 'oid' => 0, 'bid' => 0]);
    }

    /**
     * Mark materials as sold for the given order.
     * Prefers materials already reserved (status=4 + matching oid),
     * falls back to status=1 if reservation is short or missing.
     * Returns the number of materials marked sold.
     */
    public static function markSoldForOrder($pid, $tid, $oid, $count){
        if ($count <= 0) { return 0; }
        $sold = 0;

        $reserved = Material::where('pid', $pid)
            ->where('tid', $tid)
            ->where('oid', $oid)
            ->where('status', 4)
            ->limit($count)
            ->get();
        foreach ($reserved as $item) {
            $affected = Material::where('id', $item->id)
                ->where('status', 4)
                ->update(['status' => 2]);
            if ($affected) { $sold++; }
        }

        $remaining = $count - $sold;
        if ($remaining > 0) {
            $extra = Material::where('pid', $pid)
                ->where('tid', $tid)
                ->where('status', 1)
                ->limit($remaining)
                ->get();
            foreach ($extra as $item) {
                $affected = Material::where('id', $item->id)
                    ->where('status', 1)
                    ->update(['oid' => $oid, 'status' => 2]);
                if ($affected) { $sold++; }
            }
        }

        return $sold;
    }

}

