<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{

    public $timestamps = false;
    protected $table = 'reviews';
    protected $fillable = [
        'id',
        'author',
        'author_link',
        'text',
        'avatar',
        'created_at',
        'link',
        'product',
        'status'
    ];

    public static function getAll(){
        return Review::where('status', 1)->orderBy('id', 'desc')->get();
    }

}
