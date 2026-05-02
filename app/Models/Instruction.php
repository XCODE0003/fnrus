<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instruction extends Model
{
    public $timestamps = false;
    protected $table = 'instructions';
    protected $fillable = [
        'id',
        'pids',
        'title',
        'body',
        'alias',
        'views',
        'buttons',
        'updated_at'
    ];

    public static function getByPID($id){
        return Instruction::where('pids', 'like', '%"'.$id.'"%')->first();
    }

    public static function getByID($id){
        return Instruction::where('id', $id)->first();
    }

    public static function getByAilas($alias){
        return Instruction::where('alias', $alias)->first();
    }

    public static function checkAlias($alias){
        return Instruction::where('alias', $alias)->first();
    }
}
