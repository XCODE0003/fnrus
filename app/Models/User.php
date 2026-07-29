<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

class User extends Authenticatable implements JWTSubject, FilamentUser, HasName
{
    public function canAccessPanel(Panel $panel): bool
    {
        // Match AdminWebGuard: only is_ban + role_id, no is_active gate
        // (legacy admins kept access regardless of is_active).
        $minRole = (int) config('admin.min_role_id', 1);
        return !$this->is_ban
            // ТЗ §4 — blocked from the panel, storefront account untouched
            && $this->admin_blocked_at === null
            && (int) ($this->role_id ?? 0) >= $minRole;
    }

    public function getFilamentName(): string
    {
        return (string) ($this->username ?: $this->email ?: ('user#' . $this->getKey()));
    }

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
        'created_at',
        // ТЗ §4 — admin-panel access control
        'admin_blocked_at',
        'admin_blocked_by',
        'admin_blocked_reason',
        'admin_sessions_revoked_at',
        'last_admin_login_at',
        'last_admin_login_ip',
        'admin_login_count',
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
