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
        'message_template',
        'image_path',
        'updated_at'
    ];

    public const STATUSES = [
        1 => 'Рекомендуем к использованию',
        2 => 'Не рекомендуем к использованию',
        3 => 'На обновлении',
        4 => 'На свой страх и риск',
    ];

    public static function statusLabel(int $status): string
    {
        return self::STATUSES[$status] ?? 'Не определён';
    }
    public static function getListByCID($cid){
        return StatusCheat::where('cid', $cid)->get();
    }
    public static function getByID($id){
        return StatusCheat::where('id', $id)->first();
    }
}
