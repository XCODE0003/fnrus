<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalMethod extends Model
{

    public $timestamps = false;
    protected $table = 'withdrawal_methods';
    protected $fillable = [
        'id',
        'title',
        'status',
        'sort'
    ];

    public static function getByID($id){
        return Withdrawal::where('id', $id)->first();
    }
}
