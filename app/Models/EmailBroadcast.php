<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailBroadcast extends Model
{
    public $timestamps = false;
    protected $table = 'email_broadcasts';

    protected $fillable = [
        'admin_id',
        'subject',
        'body_html',
        'body_text',
        'status',
        'filters',
        'recipients_total',
        'recipients_sent',
        'recipients_failed',
        'scheduled_at',
        'started_at',
        'finished_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'filters'           => 'array',
        'recipients_total'  => 'integer',
        'recipients_sent'   => 'integer',
        'recipients_failed' => 'integer',
    ];

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_QUEUED    = 'queued';
    public const STATUS_SENDING   = 'sending';
    public const STATUS_SENT      = 'sent';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailBroadcastRecipient::class, 'broadcast_id');
    }
}
