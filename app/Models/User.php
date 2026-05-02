<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = false;
    protected $fillable = [
        'rid',
        'tid',
        'sid',
        'mid',
        'email',
        'yandex_id',
        'username',
        'password',
        'tz',
        'locale',
        'currency',
        'remember_token',
        'remember_code',
        'balance_main',
        'balance_affiliate',
        'ref_percent',
        'ref_code',
        'role_id',
        'is_ban',
        'is_active',
        'is_agreement',
        'email_notify_tickets',
        'email_notify_orders',
        'email_notify_status_changed',
        'tstep',
        'tdata',
        'created_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
