<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUse extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'coupons_uses';
    protected $fillable = [
        'promo_id',
        'chat_id',
        'shop_id',
        'created_at'
    ];

    public static function check($sid, $promo_id, $chat_id){
        return CouponUse::where('shop_id', $sid)->where('promo_id', $promo_id)->where('chat_id', $chat_id)->count();
    }

}
