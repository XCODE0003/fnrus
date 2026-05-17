<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\TelegramChannel;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Publishes formatted messages (with optional image) to one or more
 * Telegram channels/groups via the Bot API.
 *
 * Telegram HTML parser only supports a small tag whitelist:
 * <b>, <strong>, <i>, <em>, <u>, <ins>, <s>, <strike>, <del>,
 * <a href>, <code>, <pre>, <span class="tg-spoiler">.
 *
 * TinyMCE produces <p>, <br>, <strong>, <em>, <ul>/<ol>/<li>, etc.
 * sanitizeForTelegram() converts the rich output down to that subset.
 */
class TelegramPublisher
{
    private const API_BASE = 'https://api.telegram.org/bot';
    private const HTTP_TIMEOUT = 10;

    /**
     * Substitute {placeholders} in the template.
     */
    public function applyPlaceholders(string $template, array $vars): string
    {
        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{' . $key . '}'] = (string) $value;
        }
        return strtr($template, $replace);
    }

    /**
     * Convert TinyMCE-style HTML to a Telegram-compatible subset.
     * Anything outside the whitelist is stripped; block elements
     * become newlines so the message keeps its visual structure.
     */
    public function sanitizeForTelegram(string $html): string
    {
        $text = $html;

        // Block-level tags become double newlines.
        $text = preg_replace('#</(p|div|h[1-6]|li)>\s*#i', "\n", $text) ?? $text;
        $text = preg_replace('#<(p|div|h[1-6])\b[^>]*>#i', '', $text) ?? $text;

        // List items: prefix with bullets before tag is stripped.
        $text = preg_replace('#<li\b[^>]*>#i', '• ', $text) ?? $text;
        $text = preg_replace('#</?(ul|ol)\b[^>]*>\s*#i', "\n", $text) ?? $text;

        // <br>, <br/>, <br /> -> single newline
        $text = preg_replace('#<br\s*/?>#i', "\n", $text) ?? $text;

        // Normalise tags Telegram understands.
        $text = preg_replace('#<\s*strong\b[^>]*>#i', '<b>', $text) ?? $text;
        $text = preg_replace('#</\s*strong\s*>#i', '</b>', $text) ?? $text;
        $text = preg_replace('#<\s*em\b[^>]*>#i', '<i>', $text) ?? $text;
        $text = preg_replace('#</\s*em\s*>#i', '</i>', $text) ?? $text;

        // Strip any tag that is not in the Telegram whitelist.
        $allowed = '<b><i><u><s><a><code><pre>';
        $text = strip_tags($text, $allowed);

        // Decode HTML entities Telegram doesn't understand (it only
        // recognises &lt; &gt; &amp; &quot;). Anything else — &nbsp;
        // &mdash; &hellip; etc. — would otherwise render literally in
        // the channel ("✅ &nbsp;Изменение"). Trick: temporarily
        // replace the four entities with markers so they survive the
        // bulk decode, then restore them.
        $placeholders = [
            '&amp;'  => "\x00TG_AMP\x00",
            '&lt;'   => "\x00TG_LT\x00",
            '&gt;'   => "\x00TG_GT\x00",
            '&quot;' => "\x00TG_QUOT\x00",
        ];
        $text = strtr($text, $placeholders);
        $text = html_entity_decode($text, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
        $text = strtr($text, array_flip($placeholders));

        // Real non-breaking space (U+00A0) — most fonts render it but
        // visually it's identical to a regular space and Telegram
        // sometimes still shows it as a fallback box. Collapse it too.
        $text = str_replace("\xC2\xA0", ' ', $text);

        // Collapse 3+ newlines to 2.
        $text = preg_replace("#\n{3,}#", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array{ok: bool, errors: array<int, string>, sent: int}
     */
    public function broadcast(string $messageHtml, ?string $imageUrl = null, ?int $shopId = null): array
    {
        $shop = $shopId ? Shop::getByID($shopId) : Shop::getDefault();
        if (!$shop) {
            return ['ok' => false, 'errors' => ['Shop not found'], 'sent' => 0];
        }

        try {
            $token = Crypt::decryptString($shop->token);
        } catch (\Throwable $e) {
            Log::error('TelegramPublisher: cannot decrypt shop token', ['shop_id' => $shop->id]);
            return ['ok' => false, 'errors' => ['Invalid shop token'], 'sent' => 0];
        }

        $channels = TelegramChannel::activeForShop((int) $shop->id);
        if ($channels->isEmpty()) {
            return ['ok' => true, 'errors' => [], 'sent' => 0];
        }

        $payload = $this->sanitizeForTelegram($messageHtml);
        $errors = [];
        $sent = 0;

        foreach ($channels as $channel) {
            try {
                $endpoint = $imageUrl
                    ? self::API_BASE . $token . '/sendPhoto'
                    : self::API_BASE . $token . '/sendMessage';

                $body = $imageUrl
                    ? [
                        'chat_id'    => $channel->chat_id,
                        'photo'      => $imageUrl,
                        'caption'    => mb_substr($payload, 0, 1024),
                        'parse_mode' => 'HTML',
                    ]
                    : [
                        'chat_id'                  => $channel->chat_id,
                        'text'                     => mb_substr($payload, 0, 4096),
                        'parse_mode'               => 'HTML',
                        'disable_web_page_preview' => true,
                    ];

                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->asForm()
                    ->post($endpoint, $body);

                if ($response->successful() && data_get($response->json(), 'ok') === true) {
                    $sent++;
                } else {
                    $description = data_get($response->json(), 'description', $response->body());
                    $errors[] = sprintf('[%s] %s', $channel->title, $description);
                    Log::warning('TelegramPublisher: send failed', [
                        'channel_id' => $channel->id,
                        'chat_id'    => $channel->chat_id,
                        'response'   => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                $errors[] = sprintf('[%s] %s', $channel->title, $e->getMessage());
                Log::error('TelegramPublisher: exception', [
                    'channel_id' => $channel->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return [
            'ok'     => $sent > 0 || $channels->isEmpty(),
            'errors' => $errors,
            'sent'   => $sent,
        ];
    }

    /**
     * Send a single message (one chat, one HTTP call) — used by status
     * change notifications which target one configured channel instead
     * of fanning out to every row in telegram_channels.
     *
     * Image handling: if $imageUrl looks like one of our own
     * https://fnrus.com/... links, we try to resolve it to a local
     * file on disk and upload via multipart. Telegram-fetch-by-URL
     * fails on this host because the edge WAF blocks the bot's IP
     * with 403, which surfaces as "Bad Request: wrong type of the
     * web page content".
     *
     * @return array{ok: bool, errors: array<int, string>, sent: int}
     */
    public function sendToChat(string $chatId, string $messageHtml, ?string $imageUrl = null, ?int $shopId = null): array
    {
        $chatId = trim($chatId);
        if ($chatId === '') {
            return ['ok' => false, 'errors' => ['chat_id is empty'], 'sent' => 0];
        }

        $shop = $shopId ? Shop::getByID($shopId) : Shop::getDefault();
        if (!$shop) {
            return ['ok' => false, 'errors' => ['Shop not found'], 'sent' => 0];
        }

        try {
            $token = Crypt::decryptString($shop->token);
        } catch (\Throwable $e) {
            Log::error('TelegramPublisher::sendToChat: cannot decrypt shop token', ['shop_id' => $shop->id]);
            return ['ok' => false, 'errors' => ['Invalid shop token'], 'sent' => 0];
        }

        $payload = $this->sanitizeForTelegram($messageHtml);
        $localFile = $imageUrl ? $this->resolveLocalFileFromUrl($imageUrl) : null;

        try {
            if ($imageUrl && $localFile !== null) {
                // Multipart upload — bot doesn't have to fetch our domain.
                $endpoint = self::API_BASE . $token . '/sendPhoto';
                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->attach('photo', fopen($localFile, 'r'), basename($localFile))
                    ->asMultipart()
                    ->post($endpoint, [
                        'chat_id'    => $chatId,
                        'caption'    => mb_substr($payload, 0, 1024),
                        'parse_mode' => 'HTML',
                    ]);
            } elseif ($imageUrl) {
                // Remote URL fallback — Telegram fetches it itself.
                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->asForm()
                    ->post(self::API_BASE . $token . '/sendPhoto', [
                        'chat_id'    => $chatId,
                        'photo'      => $imageUrl,
                        'caption'    => mb_substr($payload, 0, 1024),
                        'parse_mode' => 'HTML',
                    ]);
            } else {
                $response = Http::timeout(self::HTTP_TIMEOUT)
                    ->asForm()
                    ->post(self::API_BASE . $token . '/sendMessage', [
                        'chat_id'                  => $chatId,
                        'text'                     => mb_substr($payload, 0, 4096),
                        'parse_mode'               => 'HTML',
                        'disable_web_page_preview' => true,
                    ]);
            }
        } catch (\Throwable $e) {
            Log::error('TelegramPublisher::sendToChat: HTTP threw', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
            return ['ok' => false, 'errors' => [$e->getMessage()], 'sent' => 0];
        }

        if (!$response->successful() || data_get($response->json(), 'ok') !== true) {
            $description = (string) data_get($response->json(), 'description', $response->body());
            Log::warning('TelegramPublisher::sendToChat: send failed', [
                'chat_id'    => $chatId,
                'used_local' => $localFile !== null,
                'status'     => $response->status(),
                'response'   => substr($response->body(), 0, 400),
            ]);
            return ['ok' => false, 'errors' => [$description], 'sent' => 0];
        }

        return ['ok' => true, 'errors' => [], 'sent' => 1];
    }

    /**
     * Maps a public URL on our own host (asset("storage/...") or
     * /i{hash}) back to the file on disk so we can multipart-upload it
     * to Telegram and skip the edge WAF.
     */
    private function resolveLocalFileFromUrl(string $url): ?string
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);
        if ($appHost && $urlHost && $appHost !== $urlHost) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        // /storage/{rel} → storage/app/public/{rel}
        if (preg_match('~^/storage/(.+)$~', $path, $m)) {
            $abs = storage_path('app/public/' . $m[1]);
            return is_file($abs) ? $abs : null;
        }
        // /i{hash} → covers/{hash}.{ext}
        if (preg_match('~^/i([A-Za-z0-9_-]+)$~', $path, $m)) {
            $row = \DB::table('attachments')->where('id', $m[1])->first();
            if ($row && !empty($row->ext)) {
                $abs = storage_path('app/public/covers/' . $row->id . '.' . $row->ext);
                return is_file($abs) ? $abs : null;
            }
        }

        return null;
    }
}
