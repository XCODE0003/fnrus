<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ShopSettings extends Model
{

    public $timestamps = false;
    protected $table = 'shops_settings';
    protected $fillable = [
        'booking_time',
        'ref_percent',
        'count_products',
        'count_categories',
        'count_buttons_profile',
        'min_sum_topup',
        'min_sum_withdrawal_card',
        'min_sum_withdrawal_balance',
        'orders_limit',
        'notify_target_id',
        'default_timezone',
        'path_avatars',
        'currency',
        'tg_notify_buys',
        'tg_notify_balance',
        'tg_notify_users',
        'btn_tg_bot_url',
        'btn_tg_bot_text',
        'btn_reviews_url',
        'btn_reviews_text',
        'btn_buy_bot_url',
        'btn_buy_bot_text',
        'policy_content_ru',
        'policy_content_en',
        'delivery_text_ru',
        'delivery_text_en',
        'btn_tg_bot_icon',
        'btn_buy_bot_icon',
        'btn_reviews_icon',
        'support_text',
        'support_btn1_text',
        'support_btn1_url',
        'support_btn2_text',
        'support_btn2_url',
        'support_btn3_text',
        'support_btn3_url'
    ];

    public static function getDefault(){
        return ShopSettings::first();
    }
}
