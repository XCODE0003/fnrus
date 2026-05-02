<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HackStatus extends Model
{
    public $timestamps = false;
    protected $table = 'hack_statuses';
    protected $fillable = [
        'id',
        'title',
    ];

    public static function getByID($id){
        return HackStatus::where('id', $id)->first();
    }
}
