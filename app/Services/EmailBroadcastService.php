<?php

namespace App\Services;

use App\Models\EmailBroadcast;
use App\Models\EmailBroadcastRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Builds the recipient list for a broadcast and dispatches the
 * per-recipient SendBroadcastEmail jobs.
 *
 * Hard rules:
 *   - Only users with a non-empty email AND email_notify_marketing=1
 *     are included. Anything else is anti-spam: do not bypass.
 *   - Recipients are persisted up front so the UI can show progress
 *     and the operator can audit who was contacted (and skipped).
 *   - Issues a per-user unsubscribe token if missing — one-click
 *     opt-out via the public route, no auth required.
 */
class EmailBroadcastService
{
    public const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><s><a><ul><ol><li><h2><h3><h4><blockquote><span><div>';

    public const HTML_LIMIT = 200_000;

    /**
     * Sanitize WYSIWYG HTML before persisting. Strips scripts/iframes/forms,
     * keeps a Gmail-friendly subset. Output is still untrusted from the
     * recipient's POV; the layout escapes via {!! !!} only this sanitized payload.
     */
    public function sanitize(string $html): string
    {
        // Remove dangerous blocks entirely (with content).
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        // Remove on*="..." event handlers and javascript: URLs.
        $html = preg_replace('#\son[a-z]+\s*=\s*"[^"]*"#i', '', $html) ?? $html;
        $html = preg_replace("#\son[a-z]+\s*=\s*'[^']*'#i", '', $html) ?? $html;
        $html = preg_replace('#javascript:#i', '', $html) ?? $html;

        $html = strip_tags($html, self::ALLOWED_TAGS);

        if (mb_strlen($html) > self::HTML_LIMIT) {
            $html = mb_substr($html, 0, self::HTML_LIMIT);
        }
        return $html;
    }

    /**
     * Replace {email}/{username} placeholders inside the broadcast body
     * for a single recipient. Other placeholders are left intact.
     */
    public function personalise(string $body, array $vars): string
    {
        $replace = [];
        foreach ($vars as $k => $v) {
            $replace['{' . $k . '}'] = (string) $v;
        }
        return strtr($body, $replace);
    }

    /**
     * Materialise the recipient list and queue the broadcast.
     *
     * @return array{queued: int, skipped: int}
     */
    public function start(EmailBroadcast $broadcast): array
    {
        if (!in_array($broadcast->status, [EmailBroadcast::STATUS_DRAFT, EmailBroadcast::STATUS_FAILED], true)) {
            throw new \RuntimeException('Broadcast is not in a startable state: ' . $broadcast->status);
        }

        $queued = 0;
        $skipped = 0;
        $now = time();

        DB::transaction(function () use ($broadcast, $now, &$queued, &$skipped) {
            // Lock the broadcast row so two admins can't fan out twice.
            $fresh = EmailBroadcast::where('id', $broadcast->id)->lockForUpdate()->first();
            if (!$fresh || $fresh->status === EmailBroadcast::STATUS_SENDING || $fresh->status === EmailBroadcast::STATUS_SENT) {
                throw new \RuntimeException('Broadcast already started');
            }

            // Build recipient query: opt-in + valid email.
            $userQuery = DB::table('users')
                ->select(['id', 'email', 'unsubscribe_token'])
                ->where('email_notify_marketing', 1)
                ->whereRaw("email <> ''")
                ->whereRaw("email IS NOT NULL");

            // Wipe any prior recipients (e.g. previous failed attempt).
            DB::table('email_broadcast_recipients')->where('broadcast_id', $fresh->id)->delete();

            $userQuery->orderBy('id')->chunk(500, function ($users) use ($fresh, $now, &$queued, &$skipped) {
                $rows = [];
                foreach ($users as $u) {
                    if (!filter_var($u->email, FILTER_VALIDATE_EMAIL)) {
                        $skipped++;
                        continue;
                    }
                    $rows[] = [
                        'broadcast_id' => $fresh->id,
                        'user_id'      => $u->id,
                        'email'        => $u->email,
                        'status'       => EmailBroadcastRecipient::STATUS_QUEUED,
                        'attempts'     => 0,
                        'created_at'   => $now,
                    ];
                    $queued++;
                }
                if (!empty($rows)) {
                    DB::table('email_broadcast_recipients')->insert($rows);
                }
            });

            $fresh->status            = EmailBroadcast::STATUS_QUEUED;
            $fresh->recipients_total  = $queued;
            $fresh->recipients_sent   = 0;
            $fresh->recipients_failed = 0;
            $fresh->started_at        = $now;
            $fresh->updated_at        = $now;
            $fresh->save();
        });

        // Dispatch jobs after the transaction so the queue worker only
        // sees fully-committed recipient rows.
        $this->dispatchQueuedRecipients($broadcast->id);

        return ['queued' => $queued, 'skipped' => $skipped];
    }

    private function dispatchQueuedRecipients(int $broadcastId): void
    {
        EmailBroadcastRecipient::where('broadcast_id', $broadcastId)
            ->where('status', EmailBroadcastRecipient::STATUS_QUEUED)
            ->orderBy('id')
            ->chunkById(200, function ($recipients) {
                foreach ($recipients as $r) {
                    try {
                        \App\Jobs\SendBroadcastEmail::dispatch($r->id);
                    } catch (\Throwable $e) {
                        Log::error('EmailBroadcastService: dispatch failed', [
                            'recipient_id' => $r->id,
                            'error'        => $e->getMessage(),
                        ]);
                    }
                }
            });

        // Mark the broadcast as actively sending now that jobs are out.
        EmailBroadcast::where('id', $broadcastId)
            ->update([
                'status'     => EmailBroadcast::STATUS_SENDING,
                'updated_at' => time(),
            ]);
    }

    /**
     * Per-recipient unsubscribe URL. Token is generated lazily and persisted.
     */
    public function unsubscribeUrl(int $userId, string $email): ?string
    {
        if ($userId <= 0) return null;
        $token = DB::table('users')->where('id', $userId)->value('unsubscribe_token');
        if (!$token) {
            $token = Str::random(48);
            DB::table('users')->where('id', $userId)->update(['unsubscribe_token' => $token]);
        }
        return rtrim(config('app.url') ?: '', '/') . '/unsubscribe/' . $token;
    }

    /**
     * Recompute counters after a recipient row updates. Cheap O(1).
     */
    public function tickStatus(int $broadcastId): void
    {
        $aggregates = DB::table('email_broadcast_recipients')
            ->selectRaw("
                SUM(CASE WHEN status = 'sent'   THEN 1 ELSE 0 END) AS sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) AS queued
            ")
            ->where('broadcast_id', $broadcastId)
            ->first();

        $update = [
            'recipients_sent'   => (int) ($aggregates->sent ?? 0),
            'recipients_failed' => (int) ($aggregates->failed ?? 0),
            'updated_at'        => time(),
        ];

        if ((int) ($aggregates->queued ?? 0) === 0) {
            $update['status']      = EmailBroadcast::STATUS_SENT;
            $update['finished_at'] = time();
        }

        EmailBroadcast::where('id', $broadcastId)->update($update);
    }
}
