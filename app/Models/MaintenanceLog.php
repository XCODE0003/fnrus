<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    public $timestamps = false;
    protected $table = 'maintenance_logs';
    protected $fillable = [
        'admin_id',
        'action',
        'target',
        'affected',
        'details',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'details'  => 'array',
        'affected' => 'integer',
    ];

    public static function record(int $adminId, string $action, ?string $target, int $affected, array $details, ?string $ip): void
    {
        static::create([
            'admin_id'   => $adminId,
            'action'     => $action,
            'target'     => $target,
            'affected'   => $affected,
            'details'    => $details,
            'ip'         => $ip,
            'created_at' => time(),
        ]);
    }
}
