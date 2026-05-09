<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Single recipient broadcast message. Bound by SendBroadcastEmail
 * job; never construct it from a controller — recipient bookkeeping
 * (status updates, attempts) lives in the job.
 */
class BroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
        public ?string $unsubscribeUrl = null,
    ) {}

    public function build(): self
    {
        $mail = $this
            ->subject($this->subjectLine)
            ->view('emails.broadcast', [
                'subject'         => $this->subjectLine,
                'body_html'       => $this->bodyHtml,
                'unsubscribe_url' => $this->unsubscribeUrl,
            ]);

        if ($this->unsubscribeUrl) {
            $mail->withSwiftMessage(function ($message) {
                $message->getHeaders()->addTextHeader(
                    'List-Unsubscribe',
                    '<' . $this->unsubscribeUrl . '>'
                );
                $message->getHeaders()->addTextHeader(
                    'List-Unsubscribe-Post',
                    'List-Unsubscribe=One-Click'
                );
            });
        }

        return $mail;
    }
}
