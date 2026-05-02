<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSubject extends Model
{
    public $timestamps = false;
    protected $table = 'tickets_subjects';
    protected $fillable = [
        'id',
        'title',
        'sort'
    ];

    public static function getList(){
        return TicketSubject::orderBy('sort')->get();
    }

    public static function getByID($id){
        return TicketSubject::where('id', $id)->first();
    }
}
