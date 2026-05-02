<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LinkAd extends Model
{

    public $timestamps = false;
    protected $table = 'links_ads';
    protected $fillable = ['id','sid','title','code','visits_total','visits_unique','created_at'];

    public static function getByCode($sid, $code){
        return LinkAd::where('sid', $sid)->where('code', $code)->first();
    }

}
