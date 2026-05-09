<?php

namespace App\Jobs;

use App\Mail\BroadcastMail;
use App\Models\EmailBroadcast;
use App\Models\EmailBroadcastRecipient;
use App\Services\EmailBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one broadcast email to one recipient. Updates the recipient
 * row with the result, then asks the service to recompute the parent
 * broadcast's counters.
 */
class SendBroadcastEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 30;

    public function __construct(public int $recipientId) {}

    public function handle(EmailBroadcastService $svc): void
    {
        $recipient = EmailBroadcastRecipient::find($this->recipientId);
        if (!$recipient) return;

        // Idempotent: if a previous attempt already shipped the email,
        // bail out before hitting the SMTP server again.
        if ($recipient->status === EmailBroadcastRecipient::STATUS_SENT) {
            return;
        }

        $broadcast = EmailBroadcast::find($recipient->broadcast_id);
        if (!$broadcast) return;

        // Honor late opt-out: if the recipient unsubscribed between
        // queueing and delivery, mark skipped without sending.
        $stillSubscribed = (int) \DB::table('users')
            ->where('id', $recipient->user_id)
            ->value('email_notify_marketing');
        if ($stillSubscribed !== 1) {
            $recipient->status = EmailBroadcastRecipient::STATUS_SKIPPED;
            $recipient->error  = 'opted_out';
            $recipient->save();
            $svc->tickStatus($broadcast->id);
            return;
        }

        $body = $svc->personalise($broadcast->body_html, [
            'email' => $recipient->email,
        ]);

        $unsubscribeUrl = $svc->unsubscribeUrl((int) $recipient->user_id, $recipient->email);

        try {
            $recipient->attempts = $recipient->attempts + 1;
            $recipient->save();

            Mail::to($recipient->email)->send(new BroadcastMail(
                $broadcast->subject,
                $body,
                $unsubscribeUrl,
            ));

            $recipient->status  = EmailBroadcastRecipient::STATUS_SENT;
            $recipient->error   = null;
            $recipient->sent_at = time();
            $recipient->save();
        } catch (\Throwable $e) {
            $recipient->status = EmailBroadcastRecipient::STATUS_FAILED;
            $recipient->error  = mb_substr($e->getMessage(), 0, 500);
            $recipient->save();
            Log::warning('SendBroadcastEmail: send failed', [
                'recipient_id' => $recipient->id,
                'error'        => $e->getMessage(),
            ]);
        }

        $svc->tickStatus($broadcast->id);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendBroadcastEmail: failed permanently', [
            'recipient_id' => $this->recipientId,
            'error'        => $e->getMessage(),
        ]);
    }
}
