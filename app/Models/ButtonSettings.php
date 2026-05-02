<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ButtonSettings extends Model
{

    public $timestamps = false;
    protected $table = 'buttons_settings';
    protected $fillable = ['sid','count_columns','updated_at'];
    public static function getByShopID($sid){
        return ButtonSettings::where('sid', $sid)->first();
    }
}
