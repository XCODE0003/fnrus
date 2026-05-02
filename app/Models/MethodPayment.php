<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MethodPayment extends Model
{

    public $timestamps = false;
    protected $table = 'shops_payment_methods';
    protected $fillable = [
        'id',
        'sid',
        'psid',
        'pid',
        'public_id',
        'public_key',
        'secret_key',
        'secret_key_two',
        'theme_code',
        'assets',
        'type',
        'active',
        'updated_at'
    ];
    
    public static function getByType($type){
        return MethodPayment::where('type', $type)->first();
    }

}
