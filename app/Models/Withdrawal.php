<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Withdrawal extends Model
{

    public $timestamps = false;
    protected $table = 'withdrawal';
    protected $fillable = ['id','user_id','sid','sum','card_number','first_name','status','method','source'];

    public static function getByID($id){
        return Withdrawal::where('id', $id)->first();
    }

    public static function check($user_id){
        return Withdrawal::where('user_id', $user_id)->where('status', 1)->first();
    }

    public static function add($user_id, $sid, $sum, $card_number, $first_name, $method, $source){
        $status = 1;
        if($method == 3){$status = 2;}
        return Withdrawal::insertGetId(['user_id' => $user_id, 'sid' => $sid, 'sum' => $sum, 'card_number' => $card_number, 'first_name' => $first_name, 'status' => $status, 'method' => $method, 'source' => $source, 'updated_at' => time(), 'created_at' => time()]);
    }

    public static function edit($id, $user_id, $key, $value){
        return Withdrawal::where('id', $id)->where('user_id', $user_id)->update([$key => $value]);
    }
}
