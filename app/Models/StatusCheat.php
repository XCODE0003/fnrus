<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusCheat extends Model
{

    public $timestamps = false;
    protected $table = 'statuses';
    protected $fillable = [
        'id',
        'cid',
        'title',
        'status',
        'updated_at'
    ];
    public static function getListByCID($cid){
        return StatusCheat::where('cid', $cid)->get();
    }
    public static function getByID($id){
        return StatusCheat::where('id', $id)->first();
    }
}
