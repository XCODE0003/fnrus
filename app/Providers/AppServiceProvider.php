<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Force HTTPS scheme for generated URLs when running behind a TLS-terminating
        // proxy. Set APP_FORCE_HTTPS=true on hosts where the proxy doesn't pass a
        // reliable X-Forwarded-Proto header. Filament otherwise emits asset URLs
        // with http:// which browsers block as Mixed Content.
        if (filter_var(env('APP_FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)) {
            URL::forceScheme('https');
        }

        Blade::directive('plural', function ($expression) {
            return "<?php echo \App\Providers\AppServiceProvider::pluralize($expression); ?>";
        });
    }

    public static function pluralize($count, $one, $few, $many)
    {
        $n = abs((int) $count);
        $mod10 = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return $count . ' ' . $one;
        } elseif ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return $count . ' ' . $few;
        } else {
            return $count . ' ' . $many;
        }
    }
}
