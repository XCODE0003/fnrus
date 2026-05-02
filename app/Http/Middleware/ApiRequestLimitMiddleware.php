<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLimitMiddleware
{
    public function handle($request, Closure $next)
    {
        $ip = $request->ip();
        $limit = 3;
        $minutes = 60;

        $key = 'api_request_limit_' . $ip;
        $requestsCount = Cache::get($key, 0);

        if ($requestsCount >= $limit) {
            return response()->json(['ok' => false, 'description' => 'Превышен лимит запросов'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        Cache::add($key, $requestsCount + 1, $minutes);

        return $next($request);
    }
}
