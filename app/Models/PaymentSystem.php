<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PaymentSystem extends Model
{

    public $timestamps = false;
    protected $table = 'payment_systems';
    protected $fillable = [
        'id',
        'title',
        'link',
        'icon',
        'type',
        'active'
    ];

    public static function getByType($type){
        return PaymentSystem::where('type', $type)->first();
    }

}
