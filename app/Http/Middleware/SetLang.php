<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

class SetLang
{
    protected $supportedLocales = ['en', 'ru'];

    public function handle($request, Closure $next)
    {
        // 1. Session has highest priority (set by LocalizationController on language switch)
        $locale = $request->session()->get('locale');

        // 2. Fall back to cookie (persists across sessions)
        if (!$locale || !in_array($locale, $this->supportedLocales)) {
            $locale = $request->cookie('locale');
        }

        // 3. Fall back to configured default
        if (!$locale || !in_array($locale, $this->supportedLocales)) {
            $locale = config('app.fallback_locale', 'ru');
        }

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }
}
