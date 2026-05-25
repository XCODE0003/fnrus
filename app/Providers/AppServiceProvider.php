<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        // Share the storefront category list with the layout so the header
        // dropdown ("Каталог читов") can show real categories without each
        // controller having to pass them.
        View::composer('user.layouts.main', function ($view) {
            try {
                $shop = \App\Models\Shop::getDefault();
                if (!$shop) {
                    $view->with('__headerCategories', collect());
                    return;
                }
                $cats = \App\Models\Category::where('sid', $shop->id)
                    ->where('cid', 0)
                    ->whereIn('visibility', [1, 2])
                    ->orderBy('sort')
                    ->get();
                // Decorate with image URL (image_site stored as raw hash; storefront
                // serves images via /i{hash}) and product counts.
                $decorated = $cats->map(function ($c) {
                    $imgUrl = !empty($c->image_site) ? '/i' . $c->image_site : '';
                    $count = 0;
                    try {
                        foreach (\App\Models\Category::getCategoriesByCID($c->id) as $sub) {
                            $count += \App\Models\Product::getCountByCatID($sub->id);
                        }
                    } catch (\Throwable $e) {}
                    return (object) [
                        'id' => $c->id,
                        'title' => $c->localized_title ?? $c->title,
                        'alias' => $c->alias,
                        'image_url' => $imgUrl,
                        'count_products' => $count,
                    ];
                });
                $view->with('__headerCategories', $decorated);
            } catch (\Throwable $e) {
                $view->with('__headerCategories', collect());
            }
        });

        Blade::directive('plural', function ($expression) {
            return "<?php echo \App\Providers\AppServiceProvider::pluralize($expression); ?>";
        });

        // @richHtml($value) — unwraps Trix attachment embeds (video, oEmbed)
        // back into raw <iframe>/<video> tags so the public site can render
        // them. See App\Support\RichHtml for the full transform.
        Blade::directive('richHtml', function ($expression) {
            return "<?php echo \\App\\Support\\RichHtml::render({$expression}); ?>";
        });

        // Universal audit log — every create/update/delete on these models
        // by an authenticated web admin gets recorded into maintenance_logs
        // and surfaces on /xoalfjamapfn/admin/maintenance-logs. Skips when
        // there is no web-authed user (cron, queue, bot webhook), so this
        // doesn't add system noise.
        $observed = [
            \App\Models\Product::class,
            \App\Models\Category::class,
            \App\Models\Tariff::class,
            \App\Models\Material::class,
            \App\Models\Order::class,
            \App\Models\Instruction::class,
            \App\Models\Sender::class,
            \App\Models\EmailBroadcast::class,
            \App\Models\Text::class,
            \App\Models\Button::class,
            \App\Models\ButtonSettings::class,
            \App\Models\ChannelSub::class,
            \App\Models\ChannelSubSettings::class,
            \App\Models\TelegramChannel::class,
            \App\Models\StatusCheat::class,
            \App\Models\User::class,
            \App\Models\Member::class,
            \App\Models\Shop::class,
            \App\Models\ShopSettings::class,
            \App\Models\Faq::class,
            \App\Models\Review::class,
            \App\Models\Attach::class,
        ];
        foreach ($observed as $modelClass) {
            if (class_exists($modelClass)) {
                $modelClass::observe(\App\Observers\AuditObserver::class);
            }
        }
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
