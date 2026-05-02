<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PaymentAsset extends Model
{

    public $timestamps = false;
    protected $table = 'shops_payment_assets';
    protected $fillable = [
        'id',
        'psid',
        'title',
        'min',
        'max',
        'currency',
        'code',
        'active'
    ];

}
