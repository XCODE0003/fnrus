<?php

namespace App\Jobs;

use App\Models\ShopSettings;
use App\Services\TelegramPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Publishes a status-change announcement to all active Telegram
 * channels/groups for the shop. Runs in the queue so a slow Telegram
 * API call does not stall the admin's HTTP request.
 *
 * Falls back gracefully (logs + drops) if the queue table is missing
 * or the connection is misconfigured — handled by the dispatcher.
 */
class BroadcastStatusChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(
        public string $messageHtml,
        public ?string $imageUrl = null,
        public ?int $shopId = null,
    ) {}

    public function handle(TelegramPublisher $publisher): void
    {
        // Status-change notifications go to ONE configured channel
        // (shops_settings.status_broadcast_chat_id) — not to every row
        // in telegram_channels. Skip silently when not configured so
        // admins can disable the feature by clearing the field.
        $chatId = trim((string) (ShopSettings::getDefault()?->status_broadcast_chat_id ?? ''));
        if ($chatId === '') {
            Log::info('BroadcastStatusChange: no chat_id configured, skipping');
            return;
        }

        $result = $publisher->sendToChat($chatId, $this->messageHtml, $this->imageUrl, $this->shopId);

        if (!$result['ok']) {
            Log::warning('BroadcastStatusChange: completed with errors', $result + ['chat_id' => $chatId]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('BroadcastStatusChange: failed permanently', [
            'error'   => $e->getMessage(),
            'shop_id' => $this->shopId,
        ]);
    }
}
