<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    public $timestamps = false;
    protected $table = 'tickets';
    protected $fillable = [
        'id',
        'sid',
        'user_id',
        'operator_id',
        'subject_id',
        'status',
        'last_answer_at',
        'expired_at',
        'created_at'
    ];
    public static function getByID($id){
        return Ticket::where('id', $id)->first();
    }

    public static function getListByUserID($user_id){
        return Ticket::where('user_id', $user_id)->orderByDesc('last_answer_at')->get();
    }

    public static function getListByOffset($user_id, $offset = 0, $limit = 10){
        return Ticket::where('user_id', $user_id)
            ->orderByDesc('last_answer_at')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public static function getByIDUserID($id, $user_id){
        return Ticket::where('id', $id)->where('user_id', $user_id)->first();
    }
}
