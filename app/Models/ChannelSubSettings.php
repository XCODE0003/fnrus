<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChannelSubSettings extends Model
{

    public $timestamps = false;
    protected $table = 'channels_sub_settings';
    protected $fillable = ['sid','text','button_check','count_columns','is_active'];
    public static function getByShopID($sid){
        return ChannelSubSettings::where('sid', $sid)->first();
    }
}
