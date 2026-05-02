<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageAll extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'pages_all';

    public static function getByShort($shortname){
        return PageAll::where('shortname', $shortname)->first();
    }
}
