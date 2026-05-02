<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Refil extends Model
{

    public $timestamps = false;
    protected $table = 'refills';
    protected $fillable = [
        'id',
        'sid',
        'owner_id',
        'user_id',
        'sum',
        'created_at'
    ];

    public static function getByID($sid, $id)
    {
        return Refil::where('sid', $sid)->where('id', $id)->first();
    }

}
