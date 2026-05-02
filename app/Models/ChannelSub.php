<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChannelSub extends Model
{

    public $timestamps = false;
    protected $table = 'channels_sub';
    protected $fillable = ['sid','cid','title','link','is_active','sort','created_at'];

    public static function getById($sid, $id){
        return ChannelSub::where('sid', $sid)->where('id', $id)->first();
    }

    public static function getAll($sid){
        return ChannelSub::where('sid', $sid)->orderBy('sort')->get();
    }

    public static function getAllActive($sid){
        return ChannelSub::where('sid', $sid)->where('is_active', 1)->orderBy('sort')->get();
    }

    public static function getByShopID($sid){
        return ChannelSub::where('sid', $sid)->first();
    }

    public static function getCountByShopID($sid){
        return ChannelSub::where('sid', $sid)->where('is_active', 1)->count();
    }


}
