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
}
