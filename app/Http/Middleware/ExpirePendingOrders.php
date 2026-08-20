<?php

namespace App\Http\Middleware;

use App\Models\Order;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ExpirePendingOrders
{
    // Cron is primary; at most one HTTP request per minute runs this fallback.
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (Cache::add('orders:auto-expire:tick', time(), 60)) {
                Order::expirePending();
            }
        } catch (\Throwable $e) {
            Log::error('Request fallback failed to expire pending orders', [
                'error' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
