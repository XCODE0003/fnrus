<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{

    public $timestamps = false;
    protected $table = 'menu';
    protected $fillable = [
        'alias',
        'mid',
        'title',
        'link',
        'icon',
        'sort'
    ];

    public static function getListBySort($mid){
        return Menu::where('mid', $mid)->orderBy('sort')->get();
    }

}
