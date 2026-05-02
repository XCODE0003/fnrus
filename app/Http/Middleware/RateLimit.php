<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class RateLimit
{
    public function handle($request, Closure $next)
    {
        $key = 'rate_limit_' . $request->input('from.id'); // используйте ID пользователя от Telegram
        $maxAttempts = 5; // максимальное количество разрешенных запросов в секунду
        $decaySeconds = 1; // секунды

        if (Cache::has($key) && Cache::get($key) >= $maxAttempts) {
            // Если у пользователя слишком много запросов, отправьте ему сообщение о лимите
            return response('Too many requests', 429); // или отправьте сообщение в Telegram о превышении лимита
        }

        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, $decaySeconds);
        }

        return $next($request);
    }
}
