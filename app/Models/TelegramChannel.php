<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramChannel extends Model
{
    public $timestamps = false;
    protected $table = 'telegram_channels';
    protected $fillable = [
        'sid',
        'title',
        'chat_id',
        'type',
        'is_active',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sid'        => 'integer',
        'sort_order' => 'integer',
    ];

    public static function activeForShop(int $sid)
    {
        return static::where('sid', $sid)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
