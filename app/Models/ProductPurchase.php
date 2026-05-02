<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'products_purchased';
    protected $fillable = [
        'product_id',
        'chat_id',
        'shop_id',
        'created_at'
    ];

    public static function check($sid, $product_id, $chat_id){
        return ProductPurchase::where('shop_id', $sid)->where('product_id', $product_id)->where('chat_id', $chat_id)->count();
    }

}
