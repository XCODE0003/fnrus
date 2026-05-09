<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailBroadcastRecipient extends Model
{
    public $timestamps = false;
    protected $table = 'email_broadcast_recipients';

    protected $fillable = [
        'broadcast_id',
        'user_id',
        'email',
        'status',
        'error',
        'attempts',
        'sent_at',
        'created_at',
    ];

    public const STATUS_QUEUED  = 'queued';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_SKIPPED = 'skipped';
}
