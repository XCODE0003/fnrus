<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Text extends Model
{

    public $timestamps = false;
    protected $table = 'texts';
    protected $fillable = ['id','sid','text','image','is_spoiler','type','updated_at','created_at'];

    public static function getByType($sid, $type){
        return Text::where('sid', $sid)->where('type', $type)->first();
    }

}
