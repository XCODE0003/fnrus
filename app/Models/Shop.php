<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Shop extends Model
{

    public $timestamps = false;
    protected $table = 'shops';
    protected $fillable = [
        'id',
        'tid',
        'username',
        'token',
        'status',
        'updated_at',
    ];

    public static function getDefault(){
        return Shop::limit(1)->first();
    }

    public static function getByID($id){
        return Shop::where('id', $id)->first();
    }
}
