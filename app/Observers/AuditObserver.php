<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MaintenanceLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Universal Eloquent observer that records create/update/delete events
 * into the maintenance_logs table so the «Журнал действий» admin page
 * has something to show.
 *
 * Recorded fields per change:
 *   • admin_id  — Auth::guard('web')->id() at the moment of the event
 *   • action    — "<Class>.created" / ".updated" / ".deleted"
 *   • target    — "<Class>#<id>"  (e.g. "Product#154")
 *   • affected  — 1 (Eloquent events fire per row)
 *   • details   — array of changed columns. For updates we keep
 *                 old → new pairs (redacted), for create/delete the
 *                 full row (redacted).
 *   • ip        — request()->ip()
 *
 * What we deliberately skip:
 *   • events with no authenticated web admin (cron, queue workers, bot
 *     webhooks) — those aren't admin actions and would flood the table.
 *   • the MaintenanceLog model itself (would recurse infinitely).
 *   • updates where the only changed columns are pure timestamps —
 *     `updated_at` ticking on every save adds zero signal.
 *
 * Sensitive columns (passwords, tokens, 2FA secrets, etc.) are
 * replaced with the literal string "[redacted]" so the audit row
 * doesn't double as a credential dump.
 */
class AuditObserver
{
    private const SENSITIVE_KEYS = [
        'password',
        'remember_token',
        'remember_code',
        'token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_recovery_email_code',
        'password_reset_token',
        'unsubscribe_token',
    ];

    /** Columns that change on every save without carrying real info. */
    private const NOISE_KEYS = ['updated_at', 'two_factor_passed_at'];

    public function created(Model $model): void
    {
        $this->record($model, 'created', $this->redact($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        if ($changes === []) {
            return;
        }

        // Skip pure-noise updates (only updated_at touched, etc.).
        $significant = array_diff_key($changes, array_flip(self::NOISE_KEYS));
        if ($significant === []) {
            return;
        }

        $original = $model->getOriginal();
        $details = [];
        foreach ($significant as $key => $newValue) {
            $details[$key] = [
                'old' => $this->redactValue($key, $original[$key] ?? null),
                'new' => $this->redactValue($key, $newValue),
            ];
        }

        $this->record($model, 'updated', $details);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', $this->redact($model->getOriginal() ?: $model->getAttributes()));
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored', ['id' => $model->getKey()]);
    }

    private function record(Model $model, string $verb, array $details): void
    {
        // Don't log audit writes themselves — would recurse.
        if ($model instanceof MaintenanceLog) {
            return;
        }

        $adminId = (int) (Auth::guard('web')->id() ?? 0);
        if ($adminId <= 0) {
            // No web-authed admin → cron / queue / bot. Not an admin action.
            return;
        }

        try {
            $shortClass = class_basename($model);
            $key = $model->getKey();
            $target = $key !== null ? $shortClass . '#' . $key : $shortClass;

            $ip = null;
            try {
                $ip = request()?->ip();
            } catch (\Throwable $e) {
                // Running outside a request — ignore.
            }

            // Cap details payload size so a giant blob (RichEditor body,
            // base64 image, etc.) doesn't bloat the row.
            $details = $this->cap($details);

            MaintenanceLog::record(
                adminId: $adminId,
                action: $shortClass . '.' . $verb,
                target: $target,
                affected: 1,
                details: $details,
                ip: $ip,
            );
        } catch (\Throwable $e) {
            // Audit must never crash a real save. Log and swallow.
            \Log::warning('AuditObserver: failed to record', [
                'model'  => $model::class,
                'verb'   => $verb,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    private function redact(array $attrs): array
    {
        $out = [];
        foreach ($attrs as $key => $value) {
            if (in_array($key, self::NOISE_KEYS, true)) {
                continue;
            }
            $out[$key] = $this->redactValue($key, $value);
        }
        return $out;
    }

    private function redactValue(string $key, mixed $value): mixed
    {
        if (in_array($key, self::SENSITIVE_KEYS, true)) {
            return '[redacted]';
        }
        if (is_string($value) && strlen($value) > 500) {
            return mb_substr($value, 0, 500) . '… (' . strlen($value) . ' bytes)';
        }
        return $value;
    }

    private function cap(array $details, int $limit = 8000): array
    {
        $json = json_encode($details, JSON_UNESCAPED_UNICODE);
        if ($json !== false && strlen($json) <= $limit) {
            return $details;
        }
        // Replace long string values with their length marker.
        foreach ($details as $k => $v) {
            if (is_array($v)) {
                foreach (['old', 'new'] as $bucket) {
                    if (isset($v[$bucket]) && is_string($v[$bucket]) && strlen($v[$bucket]) > 200) {
                        $details[$k][$bucket] = '(' . strlen($v[$bucket]) . ' bytes)';
                    }
                }
            } elseif (is_string($v) && strlen($v) > 200) {
                $details[$k] = '(' . strlen($v) . ' bytes)';
            }
        }
        return $details;
    }
}
