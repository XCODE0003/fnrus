<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'coupons';
    protected $fillable = [
        'id',
        'sid',
        'gids',
        'code',
        'sale',
        'sale_type',
        'min_sum',
        'count_uses_min',
        'count_uses_type',
        'count_uses_max',
        'count_expired',
        'count_expired_type',
        'is_new_users',
        'is_one_time'
    ];

    public static function getByID($sid, $id){
        return Coupon::where('sid', $sid)->where('id', $id)->first();
    }

    public static function getByCode($sid, $id, $code){
        return Coupon::where('sid', $sid)->where('gids', 'like', '%"'.$id.'"%')->where('code', $code)->first();
    }

}
