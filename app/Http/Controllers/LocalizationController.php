<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class LocalizationController extends Controller
{
    /**
     * Change the application locale and redirect back
     * Usage: /change-language/en or /change-language/ru
     */
    public function change(Request $request, $locale)
    {
        $supportedLocales = ['en', 'ru'];

        if (in_array($locale, $supportedLocales)) {
            $request->session()->put('locale', $locale);
            $request->session()->save();
            Cookie::queue('locale', $locale, 525600); // 1 year
            app()->setLocale($locale);
        }

        // Redirect back to the referrer or home
        return redirect()->back()->with('locale_changed', true);
    }

    /**
     * Get current locale
     * Useful for AJAX requests
     */
    public function getCurrent()
    {
        return response()->json([
            'locale' => app()->getLocale(),
            'available' => ['en', 'ru'],
        ]);
    }
}
