<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Long-poll updates from Telegram and re-inject them into the existing
 * BotController via a same-host POST to Apache.
 *
 * Why polling: the production edge (Mitelis DDoS-Mitigation) blocks
 * incoming POSTs that don't carry a browser challenge cookie, which
 * means Telegram's webhook to /s/{shop_id} returns 403. Outbound
 * HTTPS from the origin to api.telegram.org works, so we flip the
 * direction and pull updates instead.
 *
 * The forwarded request mirrors what Telegram would send: identical
 * JSON body, Content-Type: application/json, Host: fnrus.com. Apache
 * dispatches it to BotController::main exactly like a real webhook.
 */
class BotPoll extends Command
{
    protected $signature = 'bot:poll {--once : Single iteration then exit (cron mode)} {--timeout=25 : Long-poll seconds}';
    protected $description = 'Pull Telegram updates via getUpdates and forward them to /s/{sid}.';

    private const OFFSET_KEY = 'telegram_bot_poll_offset';
    private const APACHE_URL = 'http://162.248.163.75:8080/s/';

    public function handle(): int
    {
        $shop = Shop::getDefault();
        if (! $shop) {
            $this->error('No shop configured.');
            return self::FAILURE;
        }

        try {
            $token = Crypt::decryptString((string) $shop->token);
        } catch (\Throwable $e) {
            $this->error('Cannot decrypt bot token: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($token === '') {
            $this->error('Bot token is empty.');
            return self::FAILURE;
        }

        $shopId = (int) $shop->id;
        $forwardUrl = self::APACHE_URL . $shopId;
        $timeout = max(0, (int) $this->option('timeout'));
        $once = (bool) $this->option('once');

        // Hard upper bound on a single command run so cron tick + long-poll
        // doesn't drift. --max-time=55 in queue:work uses the same trick.
        $deadline = time() + 55;

        do {
            $offset = (int) Cache::get(self::OFFSET_KEY, 0);

            try {
                $response = Http::timeout($timeout + 5)->get(
                    'https://api.telegram.org/bot' . $token . '/getUpdates',
                    [
                        'offset'  => $offset,
                        'timeout' => $timeout,
                        'limit'   => 50,
                    ],
                );
            } catch (\Throwable $e) {
                Log::warning('bot:poll getUpdates threw', ['error' => $e->getMessage()]);
                if ($once) { return self::SUCCESS; }
                sleep(2);
                continue;
            }

            if (! $response->ok()) {
                Log::warning('bot:poll getUpdates HTTP error', ['status' => $response->status(), 'body' => substr($response->body(), 0, 400)]);
                if ($once) { return self::SUCCESS; }
                sleep(2);
                continue;
            }

            $payload = $response->json();
            if (! ($payload['ok'] ?? false) || ! is_array($payload['result'] ?? null)) {
                Log::warning('bot:poll getUpdates malformed', ['payload' => substr(json_encode($payload), 0, 400)]);
                if ($once) { return self::SUCCESS; }
                sleep(2);
                continue;
            }

            $updates = $payload['result'];
            $maxId = $offset - 1;

            foreach ($updates as $update) {
                $updateId = (int) ($update['update_id'] ?? 0);
                if ($updateId > $maxId) {
                    $maxId = $updateId;
                }

                try {
                    $forward = Http::timeout(15)
                        ->withHeaders(['Host' => 'fnrus.com', 'Content-Type' => 'application/json'])
                        ->withoutVerifying()
                        ->withBody(json_encode($update, JSON_UNESCAPED_UNICODE), 'application/json')
                        ->post($forwardUrl);

                    if (! $forward->ok()) {
                        Log::warning('bot:poll forward non-2xx', [
                            'update_id' => $updateId,
                            'status'    => $forward->status(),
                            'body'      => substr($forward->body(), 0, 400),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('bot:poll forward threw', [
                        'update_id' => $updateId,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            if ($maxId >= $offset) {
                Cache::put(self::OFFSET_KEY, $maxId + 1, now()->addDays(30));
            }

            if ($updates) {
                $this->info(sprintf('forwarded %d update(s), offset → %d', count($updates), $maxId + 1));
            }

            if ($once) {
                return self::SUCCESS;
            }
        } while (time() < $deadline);

        return self::SUCCESS;
    }
}
