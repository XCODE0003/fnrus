<?php

namespace App\Models;

use App\Http\Controllers\SenderController;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class Sender extends Model
{

    public $timestamps = false;
    protected $table = 'senders';
    protected $fillable = ['id','sid','title','message','buttons','disable_web_page_preview','has_spoiler','forward_link','image','count_all','count_success','count_fail','type','status','started_at','updated_at','created_at'];

    public static function getByID($sid, $id){
        return Sender::where('sid', $sid)->where('id', $id)->first();
    }
    public static function deleteByID($sid, $id){
        return Sender::where('sid', $sid)->where('id', $id)->delete();
    }
    public static function getAllBySID($sid){
        return Sender::where('sid', $sid)->orderByDesc('created_at')->get();
    }
    public static function getByDate($started_at){
        return Sender::where('started_at', $started_at)->where('status', 1)->orderBy('id')->get();
    }
    public static function changeByID($id, $sql){
        return Sender::where('id', $id)->update($sql);
    }

}
