<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
