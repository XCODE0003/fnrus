<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Role extends Model
{

    public $timestamps = false;
    protected $table = 'roles';
    protected $fillable = [
        'id',
        'title',
        'sort'
    ];

    public static function getByID($id){
        return Role::where('id', $id)->first();
    }

    public static function getEndSort(){
        return Role::orderByDesc('sort')->limit(1)->first()->sort + 1;
    }

}
