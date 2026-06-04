<?php

use App\Models\HackStatus;
use App\Models\ShopSettings;
use App\Models\Order;
use App\Models\Material;
use App\Models\Shop;
use App\Models\Review;
use App\Models\Tariff;
use App\Models\Category;
use App\Models\StatusCheat;
use App\Models\Product;
use App\Models\Faq;
use App\Models\AboutItem;
use App\Models\PageAll;
use App\Models\Member;
use App\Models\Currency;
use App\Models\Instruction;
use App\Models\TicketSubject;
use App\Models\WithdrawalMethod;
use App\Models\MethodPayment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SendController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\SenderController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\FilmController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\YandexAuthController;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\LocalizationController;

// Debug routes disabled in production
// Route::get('/conv', function (){
//     dd(Currency::convertOnline('RUB', 'USD', 1000));
// });

// Route::get('/recount5', function (){
//     $exp = DB::table('materials')->where('body', 'BPF3RCADZE4XL2V4G3PA:54p1OLG8')->first();
//     var_dump($exp);
// });

//Route::get('/email/{blade}', function ($blade) {
//
//    if($blade == 'order'){
//        return view('emails.order-receipt', ['product_title' => 'test', 'product_sum' => '100₽', 'order_link' => 'https://t.me/']);
//    }
//
//    if($blade == 'ticket'){
//        return view('emails.ticket', ['subject' => 'Тема', 'text' => 'Тут будет текст тикета', 'product_sum' => '100₽', 'order_link' => 'https://t.me/']);
//    }
//
//    if($blade == 'reset'){
//        return view('emails.reset-code', ['code' => 'DDST76', 'product_title' => 'test', 'product_sum' => '100₽', 'order_link' => 'https://t.me/']);
//    }
//
//})->where('blade', '[a-z]+');

// Stub routes removed - were blocking /i{hash} image routes

Route::group([], function () {

// Language switching routes
Route::get('/change-language/{locale}', [LocalizationController::class, 'change'])
    ->name('locale.change')
    ->where('locale', '[a-z]{2}');

// Legacy support
Route::get('/lang/{locale}', [LocalizationController::class, 'change'])
    ->name('locale.legacy')
    ->where('locale', '[a-z]{2}');

// API endpoint for current locale
Route::get('/api/locale/current', [LocalizationController::class, 'getCurrent'])
    ->name('locale.current');

// Public one-click unsubscribe (RFC 8058). No auth — protected by opaque token.
Route::match(['GET', 'POST'], '/unsubscribe/{token}', [\App\Http\Controllers\UnsubscribeController::class, 'unsubscribe'])
    ->where('token', '[A-Za-z0-9]{16,64}');

// Public password-reset form (force-reset issued by an admin). Token verified server-side.
Route::get('/password-reset/{token}', function ($token) {
    return view('user.password-reset', ['token' => $token]);
})->where('token', '[A-Za-z0-9]{64}');

Route::get('/fk-verify.html', function () {
    return response(file_get_contents(public_path('fk-verify.html')))->header('Content-Type', 'text/plain');
});

Route::get('/login/{hash}', [AuthController::class, 'auth_by_link'])->where('hash', '[a-zA-Z0-9]+');

Route::get('/oauth/yandex/redirect', [YandexAuthController::class, 'redirect']);
Route::get('/oauth/yandex/callback', [YandexAuthController::class, 'callback']);
Route::get('/oauth/telegram/callback', [\App\Http\Controllers\TelegramAuthController::class, 'callback']);

Route::get('/reconstruction', function () {
    return view('user.reconstruction');
});

Route::get('/blocked', function () {
    return view('user.blocked');
});

Route::get('/sender', [SenderController::class, 'cron_start']);

Route::get('/p/{shortname}', function ($shortname) {

    $page = PageAll::getByShort($shortname);
    if(!$page){exit('Page not found!');}

    return view('user/in', [
        'title' => $page->title,
        'body' => $page->text
    ]);
});


Route::get('/file/{hash}', [StorageController::class, 'file'])
    ->middleware('file.purchased')
    ->where('hash', '[a-zA-Z0-9-_]+');


Route::get('/i{hash}', [StorageController::class, 'image'])->where('hash', '[a-zA-Z0-9-_]+');

// Custom-Trix-toolbar upload — gated by the Filament admin auth bridge
// so only authenticated admins can hit it. Hosted off the panel path
// to avoid colliding with Filament's own Livewire routes.
Route::post('/admin-editor-upload', [\App\Http\Controllers\EditorUploadController::class, 'upload'])
    ->middleware([
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\FilamentSiteAuthBridge::class,
    ])
    ->name('admin.editor-upload');
Route::post('/s/{shop_id}', [BotController::class, 'main'])->where('shop_id', '[0-9]+');
Route::get('/inv', [InvoiceController::class, 'inv']);
Route::post('/payment/check/qw/{id}', [InvoiceController::class, 'qwCheck'])->where('id', '[0-9]+');
Route::get('/invoice/{hash}', [InvoiceController::class, 'index'])->where('hash', '[a-zA-Z0-9]+');
Route::get('/invoice/{hash}/check', function ($hash) {
    $o = Order::where('hash', $hash)->first();
    if (!$o) {
        return response()->json(['ok' => false]);
    }

    $s = Shop::where('id', $o->sid)->first();
    return view('check_status', ['hash' => $hash, 'shop_username' => $s->username]);
})->where('hash', '[a-zA-Z0-9]+');

Route::get('/invoice/{hash}/status', [InvoiceController::class, 'status'])->where('hash', '[a-zA-Z0-9]+');
Route::get('/invoice/{hash}/{type}/{asset_id}', [InvoiceController::class, 'method'])->where('hash', '[a-zA-Z0-9]+')->where('type', '[a-z]+')->where('asset_id', '[0-9]+');
Route::any('/pay/callback/{type}', [InvoiceController::class, 'callback_payment'])->where('type', '[a-z]+');
Route::post('/telegram/webhook', [InvoiceController::class, 'telegramWebhook']);


Route::get('/test', [TicketController::class, 'autoCloseCron']);

Route::get('/create-order', [InvoiceController::class, 'createOrder']);

Route::get('/instruction/{alias}', function ($alias) {
    $instruction = Instruction::getByAilas($alias);
    $instruction_buttons = [];

    if ($instruction && $instruction->buttons != "null") {
        $instruction_buttons = json_decode($instruction->buttons, true);
    }

    $instruction_title = $instruction->title;

    $instruction_body = '';
    if ($instruction) {
        $instruction_body = $instruction->body;
    }

    $instruction->views += 1;
    $instruction->save();

    $faq = Faq::getListByInstruction($instruction->id);

    return view('user/instruction', [
        'title' => $instruction_title,
        'instruction_buttons' => $instruction_buttons,
        'instruction' => $instruction_body,
        'faq' => $faq,
    ]);
})->where('hash', '[a-zA-Z0-9-]+');

Route::get('/delivery/{hash}', function ($hash) {
    $order = Order::getByHashNoAuth($hash);
    if (!$order) {
        abort(404);
    }

    // ---- IDOR guard ----------------------------------------------------
    // An order that belongs to a registered account (bid > 0) may only be
    // opened by that same account. We resolve the viewer from the JWT
    // `session_token` cookie (same mechanism as FilamentSiteAuthBridge).
    // Guest orders (bid == 0) keep capability access via the secret hash
    // (delivery links sent by email / Telegram bot).
    if ((int) ($order->bid ?? 0) > 0) {
        $viewerId = 0;
        if ($tok = request()->cookie('session_token')) {
            try {
                $viewerId = (int) (\PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($tok)->authenticate()->id ?? 0);
            } catch (\Throwable $e) {
                $viewerId = 0;
            }
        }
        if ($viewerId !== (int) $order->bid) {
            abort(404);
        }
    }
    // --------------------------------------------------------------------

    if ($order->status == 1) {
        return redirect()->back();
    }

    $material = Order::getMaterialsByIDNoAuth($order->id);
    $instruction = Instruction::getByPID($order->pid);
    $instruction_buttons = [];

    if ($instruction && $instruction->buttons != "null") {
        $instruction_buttons = json_decode($instruction->buttons, true);
    }

    $instruction_body = '';
    if ($instruction) {
        $instruction_body = $instruction->body;
    }

    // Grant the current browser session permission to download every
    // /file/{X} that this purchase references — picked up by the
    // EnsureFilePurchased middleware on the /file/{hash} route.
    $accessible = (array) session('access.files', []);
    foreach ([$material, $instruction_body] as $blob) {
        if (preg_match_all('~/file/([a-zA-Z0-9_-]+)~', (string) $blob, $m)) {
            foreach ($m[1] as $fid) {
                if (!in_array($fid, $accessible, true)) {
                    $accessible[] = $fid;
                }
            }
        }
    }
    if (count($accessible) > 500) {
        $accessible = array_slice($accessible, -500);
    }
    session(['access.files' => $accessible]);

    if ($instruction){
        $faq = Faq::getListByInstruction($instruction->id);
    } else {
        $faq = [];
    }

    $shop_set = ShopSettings::getDefault();
    $locale = app()->getLocale();
    $delivery_text = $locale === 'en' ? $shop_set->delivery_text_en : $shop_set->delivery_text_ru;

    return view('user/delivery', [
        'title' => __('site.page_title_delivery'),
        'product_title' => $order->title,
        'date_payment' => date('d.m.Y в H:i', $order->payment_at),
        'material' => $material,
        'instruction' => $instruction,
        'instruction_buttons' => $instruction_buttons,
        'instruction_body' => $instruction_body,
        'faq' => $faq,
        'delivery_text' => $delivery_text
    ]);
})->where('hash', '[a-zA-Z0-9-]+');


//Route::middleware('ip.whitelist')->group(function () {

Route::any('/', function () {
    $shop = Shop::getDefault();
    $shop_set = ShopSettings::getDefault();
    $referralCode = Session::get('referral_code');
    $categories = Category::list_web_by_cid(0);
    $faq = Faq::getListByInstruction(0);
    return view('user/index', [
        'referralCode' => $referralCode,
        'categories' => $categories->getData()->result,
        'shop' => $shop,
        'reviews' => Review::getAll(),
        'faq' => $faq,
        'shop_set' => $shop_set
    ]);
})->name('home');

Route::any('/r/{hash}', function ($hash) {
    Session::put('referral_code', $hash);
    return redirect('/');
})->where('hash', '[a-zA-Z0-9-]+');

Route::get('/status', function () {
    $categories = Category::list_web_by_cid(0);

    $categories_json = [];

    foreach ($categories->getData()->result as $c) {

        $cheats_json = [];

        $cheats = StatusCheat::getListByCID($c->id);

        foreach ($cheats as $p) {

            $status_class = 'undetected';
            if ($p->status == 1) {
                $status_class = 'recommend';
            }
            if ($p->status == 2) {
                $status_class = 'not-recommend';
            }
            if ($p->status == 3) {
                $status_class = 'on-update';
            }
            if ($p->status == 4) {
                $status_class = 'risk';
            }

            $cheats_json[] = [
                'title' => $p->title,
                'status' => $status_class
            ];
        }

        if ($cheats_json) {
            $categories_json[] = [
                'title' => $c->title,
                'image' => $c->image,
                'image_site' => $c->image_site,
                'cheats' => $cheats_json,
            ];
        }

    }

    return view('user/status', ['title' => __('site.page_title_statuses'), 'categories' => $categories_json]);
});
Route::get('/about', function () {
    $shop = Shop::getDefault();
    $categories = Category::list_web_by_cid(0);
    $about_items = AboutItem::getAllSorted();
    return view('user/about', ['title' => __('site.page_title_about'), 'categories' => $categories->getData()->result, 'shop' => $shop, 'about_items' => $about_items]);
});
Route::get('/games', function () {
    $shop = Shop::getDefault();
    $shop_set = ShopSettings::getDefault();
    $categories = Category::list_web_by_cid(0);
    return view('user/games', [
        'title' => __('site.games_page_title'),
        'categories' => $categories->getData()->result,
        'shop' => $shop,
        'shop_set' => $shop_set,
    ]);
})->name('games');
Route::get('/reviews', function () {
    $shop = Shop::getDefault();
    $shop_set = ShopSettings::getDefault();
    // Products list for the "select product" dropdown in the review form
    $products = \App\Models\Product::where('sid', $shop->id)
        ->whereIn('visibility', [1, 2])
        ->orderBy('sort')
        ->get(['id', 'title', 'alias']);
    return view('user/reviews', [
        'title' => __('site.reviews_page_title'),
        'reviews' => Review::getAll(),
        'shop' => $shop,
        'shop_set' => $shop_set,
        'products' => $products,
    ]);
})->name('reviews');
Route::get('/policy', function () {
    $shop_set = ShopSettings::getDefault();
    $locale = app()->getLocale();
    $policy_content = $locale === 'en' ? $shop_set->policy_content_en : $shop_set->policy_content_ru;
    return view('user/policy', ['title' => __('site.page_title_policy'), 'policy_content' => $policy_content]);
});

Route::get('/my/profile', function () {
    return view('user/profile', ['title' => __('site.page_title_profile')]);
});
Route::get('/my/orders', function () {
    return view('user/orders', ['title' => __('site.page_title_orders')]);
});
Route::get('/my/referral', function () {
    return view('user/referral', ['title' => __('site.page_title_referral'), 'methods' => WithdrawalMethod::where('status', 1)->orderBy('sort')->get()]);
});
// Route::get('/my/tickets', function () {
//     return view('user/tickets', ['title' => 'Тикеты', 'subjects' => TicketSubject::where('status', 1)->orderBy('sort')->get()]);
// });


/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
| URL prefix is driven by config('admin.prefix') (env ADMIN_URL_PREFIX).
| Set it to a long random string in production so the panel address is
| unguessable. Legacy /admin (when a custom prefix is configured) and
| /wp-admin / /administrator are unconditionally 404'd by
| App\Http\Middleware\BlockLegacyAdminPath (registered globally).
|
| Stack:
|   ip.whitelist  — only allow listed IPs/CIDRs (config('admin.allowed_ips'))
|   auth          — require an authenticated session/JWT
|   admin         — require role_id >= config('admin.min_role_id')
|   2fa           — require TOTP verification for the current session
*/
/*
| Note: client-side auth is JWT-based (see resources/js/admin/* and AuthController).
| Web routes therefore enforce only the IP allow-list at the server; admin role
| and 2FA checks happen at the API level on /api/admin/* via the
| 'admin' + '2fa' middleware. Anyone hitting /<prefix>/* without a JWT will
| see the SPA shell and be redirected to /login by the JS bootstrap.
*/
// Filament migration: when LEGACY_ADMIN_ENABLED=false (default), the old
// Blade-based admin is replaced by a redirect to the new Filament panel
// at config('filament.path'). The full route group stays in this file so
// it can be re-enabled by flipping the env flag without code changes.
$legacyAdminEnabled = filter_var(env('LEGACY_ADMIN_ENABLED', false), FILTER_VALIDATE_BOOLEAN);

if (! $legacyAdminEnabled) {
    Route::any(config('admin.prefix', 'admin') . '/{any?}', function () {
        return redirect('/' . trim((string) config('filament.path', 'admin'), '/'));
    })->where('any', '.*');
}

if ($legacyAdminEnabled) {
Route::middleware(['ip.whitelist', 'admin.web'])
    ->prefix(config('admin.prefix', 'admin'))
    ->group(function () {
        Route::get('/', function () {
            return redirect('/' . trim(config('admin.prefix', 'admin'), '/') . '/stats');
        });

        Route::middleware([])->group(function () {
            Route::get('/stats', function () {
                return view('admin.dashboard.index', ['title' => 'Статистика']);
            })->name('admin.stats');

            Route::get('/faq', function () {
                return view('admin.dashboard.faq', ['title' => 'Вопрос-ответ']);
            })->name('admin.faq');

            Route::get('/reviews', function () {
                return view('admin.dashboard.reviews', ['title' => 'Отзывы']);
            })->name('admin.reviews');

            Route::get('/create/shop', function () {
                return view('admin.dashboard.create-shop', ['title' => 'Создание магазина']);
            });

            Route::get('/products', function () {
                return view('admin.dashboard.products.index', ['title' => 'Товары']);
            });
            Route::get('/categories', function () {
                return view('admin.dashboard.products.categories', ['title' => 'Категории']);
            });
            Route::get('/materials', function () {
                return view('admin.dashboard.products.materials', ['title' => 'Материалы']);
            });
            Route::get('/promocodes', function () {
                return view('admin.dashboard.products.coupons', ['title' => 'Промокоды']);
            });
            Route::get('/exports', function () {
                return view('admin.dashboard.products.exports', ['title' => 'История экспорта']);
            });

            Route::get('/orders', function () {
                return view('admin.dashboard.orders', ['title' => 'Заказы']);
            });

            Route::get('/members', function () {
                return view('admin.dashboard.members', ['title' => 'Пользователи']);
            });
            Route::get('/members/export', function () {
                return view('admin.dashboard.members-export', ['title' => 'Выгрузка пользователей']);
            });
            Route::get('/members/import', function () {
                return view('admin.dashboard.members-import', ['title' => 'Импорт пользователей']);
            });

            Route::get('/senders', function () {
                return view('admin.dashboard.senders', ['title' => 'Рассылки']);
            });
            Route::get('/links', function () {
                return view('admin.dashboard.links', ['title' => 'Рекламные ссылки']);
            });
            Route::get('/files', function () {
                return view('admin.dashboard.files', ['title' => 'Файлы']);
            });
            Route::get('/instructions', function () {
                return view('admin.dashboard.instructions', ['title' => 'Инструкции']);
            });
            Route::get('/withdrawals', function () {
                return view('admin.dashboard.withdrawal', ['title' => 'Выводы средств']);
            });
            Route::get('/statuses', function () {
                return view('admin.dashboard.statuses', ['title' => 'Статусы читов']);
            });
            Route::get('/maintenance', function () {
                return view('admin.dashboard.maintenance', ['title' => 'Обслуживание']);
            });
            Route::get('/email-broadcasts', function () {
                return view('admin.dashboard.email-broadcasts', ['title' => 'Email-рассылки']);
            });
            Route::get('/roles', function () {
                return view('admin.dashboard.roles', ['title' => 'Управление ролями']);
            });
            Route::get('/settings/constructor', function () {
                return view('admin.dashboard.settings.constructor', ['title' => 'Конструктор']);
            });
            Route::get('/settings/payments', function () {
                return view('admin.dashboard.settings.payments', ['title' => 'Настройки оплаты']);
            });
            Route::get('/settings/token', function () {
                return view('admin.dashboard.settings.token', ['title' => 'Секретный токен']);
            });
            Route::get('/settings/notify', function () {
                return view('admin.dashboard.settings.notify', ['title' => 'Уведомления']);
            });
            Route::get('/settings/site-buttons', function () {
                return view('admin.dashboard.settings.site-buttons', ['title' => 'Кнопки сайта']);
            });
            Route::get('/settings/policy', function () {
                return view('admin.dashboard.settings.policy', ['title' => 'Пользовательское соглашение']);
            });
            Route::get('/settings/about', function () {
                return view('admin.dashboard.settings.about', ['title' => 'Страница О нас']);
            });
            Route::get('/settings/delivery-text', function () {
                return view('admin.dashboard.settings.delivery-text', ['title' => 'Текст после оплаты']);
            });
            Route::get('/settings/support', function () {
                return view('admin.dashboard.settings.support', ['title' => 'Техподдержка']);
            });
        });

        // 2FA enrolment / verification UI views — auth check happens via JS+API.
        Route::get('/2fa', function () {
            return view('admin.dashboard.two-factor', ['title' => 'Двухфакторная защита']);
        })->name('admin.2fa.page');

        Route::get('/access', function () {
            return view('admin.dashboard.access', ['title' => 'Контроль доступа']);
        })->name('admin.access.page');
    });
} // end legacy admin gate

Route::get('/{alias}', function ($alias) {
    $shop = Shop::getDefault();
    $shop_set = ShopSettings::getDefault();
    $sid = $shop->id;

    $cat = Category::getByAliasWeb($sid, 0, $alias);
    if (!$cat) {
        abort(404);
    }

    $recommendations_json = [];
    $categories_json = [];

    $categories = Category::list_web_by_cid($cat->id);

    if ($categories->getData()->result) {
        foreach ($categories->getData()->result as $c) {
            $products = Product::list_web_by_cid($c->id);

            $products_json = [];

            // dd($products);

            if ($products->getData()->result) {
                foreach ($products->getData()->result as $product) {

                    $c = Category::getByID($product->sid, $product->cid);

                    $products_json[] = [
                        'title' => $product->title,
                        'image' => $product->image,
                        'image_site' => $product->image_site,
                        'status_class' => $product->status_class,
                        'status_title' => $product->status_title,
                        'advantages' => $product->advantages,
                        'hack_status' => $product->hack_status,
                        'price' => \App\Models\Tariff::where('pid', $product->id)->min('price'),
                        'alias' => $c->alias . '/' . $product->alias
                    ];
                }
                $categories_json[] = [
                    'title' => $c->localized_title,
                    'products' => $products_json
                ];
            }
        }
    }

    $recommendations = Category::list_web_recommendations($cat->id);
    if ($recommendations) {
        $recommendations_json = $recommendations->getData()->result;
    }

    $faq = Faq::getListByInstruction(0);

    return view('user/game', [
        'id' => $cat->id,
        'title' => $cat->localized_title,
        'description' => $cat->description,
        'hero_image' => $cat->image_hero,
        'alias' => $alias,
        'display_products' => $cat->display_products,
        'cat_one' => '',
        'cat_two' => '',
        'categories' => $categories_json,
        'recommendations' => $recommendations_json,
        'shop' => $shop,
        'shop_set' => $shop_set,
        'faq' => $faq
    ]);
});

Route::get('/{alias}/{alias_two}', function ($alias, $alias_two) {
    $shop = Shop::getDefault();
    $sid = $shop->id;

    $cat_alias = Category::getByAliasWeb($sid, 0, $alias);
    if (!$cat_alias) {
        abort(404);
    }

    $product = [];
    $recommendations_json = [];
    $products_json = [];

    $cat_alias_two = Category::getByAliasWeb($sid, $cat_alias->id, $alias_two);
    $product = Product::getByAliasWeb($sid, $alias_two);
//
//    $page_route = 'game';
//    $id = 0;
//    $title = $cat_alias_two->title;
//    $tariffs = [];

    $display_products = 0;

    if ($cat_alias_two and $cat_alias_two->cid == $cat_alias->id) {
        $id = $cat_alias_two->id;
        $title = $cat_alias_two->title;
        $alias_two = $cat_alias_two->alias;
        $page_route = 'game';
        $tariffs = [];
        $display_products = $cat_alias_two->display_products ?? 0;

        $products = Product::list_web_by_cid($cat_alias_two->id);
        if ($products) {
            $products_json = $products->getData()->result;
        }

        $recommendations = Category::list_web_recommendations($id);
        if ($recommendations) {
            $recommendations_json = $recommendations->getData()->result;
        }
    }

    if ($product and $product->cid == $cat_alias->id) {
        $id = $product->id;
        $title = $product->title;
        $alias_two = $product->alias;
        $page_route = 'cheat';
        $tariffs = Tariff::getListByPid($sid, $product->id);

        $recommendations = Product::list_web_recommendations($id);
        if ($recommendations) {
            $recommendations_json = $recommendations->getData()->result;
        }
    }

    if (!isset($page_route)) {
        abort(404);
    }

    $faq = Faq::getListByInstruction(0);

    return view('user/' . $page_route, [
        'id' => $id,
        'title' => $title,
        'cat_one' => $cat_alias,
        'cat_two' => $cat_alias_two,
        'alias' => $cat_alias->alias,
        'alias_two' => $alias_two,
        'categories' => [],
        'products' => $products_json,
        'tariffs' => $tariffs,
        'recommendations' => $recommendations_json,
        'product' => $product,
        'shop' => $shop,
        'faq' => $faq,
        'display_products' => $display_products,
    ]);
});

Route::get('/{alias}/{alias_two}/{alias_product}', function ($alias, $alias_two, $alias_product) {
    $shop = Shop::getDefault();
    $sid = $shop->id;

    $recommendations_json = [];

    $cat_alias = Category::getByAliasWeb($sid, 0, $alias);
    if (!$cat_alias) {
        abort(404);
    }

    $cat_alias_two = Category::getByAliasWeb($sid, $cat_alias->id, $alias_two);
    if (!$cat_alias_two) {
        abort(404);
    }

    $product = Product::getByAliasWeb($sid, $alias_product);
    if (!$product) {
        abort(404);
    }

    if ($cat_alias_two->cid != $cat_alias->id || $product->cid != $cat_alias_two->id) {
        abort(404);
    }

    $recommendations = Product::list_web_recommendations($product->id);
    $recommendations_json = [];
    if ($recommendations) {
        // list_web_recommendations may swallow an exception and return
        // {ok: false, description: ...} without a "result" key — guard
        // so an unrelated rec failure does not 500 the whole product page.
        $payload = $recommendations->getData();
        if (isset($payload->result) && is_array($payload->result)) {
            $recommendations_json = $payload->result;
        } elseif (isset($payload->result)) {
            $recommendations_json = (array) $payload->result;
        }
    }

    $status_title = '';
    $status_class = '';

    $status = StatusCheat::getByID($product->status_id);

    if ($status->status == 0) {
        $status_title = 'Неизвестно';
        $status_class = 'undetected';
    }
    if ($status->status == 1) {
        $status_title = 'Рекомендуем';
        $status_class = 'undetected';
    }
    if ($status->status == 2) {
        $status_title = 'Не рекомендуем';
        $status_class = 'on-update';
    }
    if ($status->status == 3) {
        $status_title = 'На обновлении';
        $status_class = 'on-update';
    }
    if ($status->status == 4) {
        $status_title = 'На страх и риск';
        $status_class = 'risk';
    }

    $tariffs = Tariff::getListByPid($sid, $product->id);

    return view('user/cheat', [
        'id' => $product->id,
        'title' => $product->title,
        'cat_one' => $cat_alias,
        'cat_two' => $cat_alias_two,
        'product' => $product,
        'tariffs' => $tariffs,
        'status_title' => $status_title,
        'status_class' => $status_class,
        'recommendations' => $recommendations_json,
        'shop' => $shop
    ]);
});
});
