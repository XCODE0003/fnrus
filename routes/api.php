<?php

use Illuminate\Support\Facades\Route;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\InstructionController;
use App\Http\Controllers\StatusCheatController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\ButtonController;
use App\Http\Controllers\ButtonSetController;
use App\Http\Controllers\ShopSettingsController;
use App\Http\Controllers\AboutItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\LinkAdsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SenderController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialsExportController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChannelSubController;
use App\Http\Controllers\ChannelSubSettingsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StatController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MethodPaymentController;
use App\Http\Controllers\TextController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\CheatController;
use App\Http\Controllers\SidebarController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentAssetController;
use App\Models\Menu;

Route::domain('fnrus.com')->middleware('cors')->group(function () {
    Route::controller(AttachmentController::class)->prefix('attachments')->group(function () {
        Route::post('files/upload', 'uploadFiles');
    });
});

Route::group([], function () {

// Публичный поиск — лимит запросов (search-as-you-type + анти-DDoS).
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/cheats/search', [CheatController::class, 'search']);
    Route::post('/search', [ProductController::class, 'search']);
});

// Публичная отправка отзыва посетителем (попадает на модерацию) — строгий лимит против спама.
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/reviews/submit', [\App\Http\Controllers\ReviewController::class, 'submit']);
});

// Публичный завершающий шаг принудительного сброса пароля (без auth, by token).
Route::post('/password-reset/{token}', [\App\Http\Controllers\PasswordResetController::class, 'complete'])
    ->where('token', '[A-Za-z0-9]{64}');

// Главного-админа управление безопасностью (IP-whitelist + разблокировка login).
Route::middleware(['auth', 'admin', '2fa'])->prefix('admin/access')->group(function () {
    Route::get('ips',           [\App\Http\Controllers\AdminAccessController::class, 'ips']);
    Route::post('ips',          [\App\Http\Controllers\AdminAccessController::class, 'addIp']);
    Route::delete('ips/{id}',   [\App\Http\Controllers\AdminAccessController::class, 'deleteIp'])->where('id','[0-9]+');
    Route::post('login/unlock', [\App\Http\Controllers\AdminAccessController::class, 'unlockLogin']);
    Route::get('login/attempts',[\App\Http\Controllers\AdminAccessController::class, 'loginAttempts']);
});

// Sidebar: navigation only, no sensitive data — exempt from 2FA so a freshly
// logged-in admin (with no 2FA set up yet) can still see the menu and
// navigate to /admin/2fa for enrolment.
Route::middleware(['admin'])->group(function () {
    Route::get('/sidebar', [SidebarController::class, 'index']);
});

// Для админов
Route::middleware(['admin', '2fa'])->group(function () {

    // Управление тарифами
    Route::controller(TariffController::class)->prefix('tariffs')->group(function () {
        Route::get('{id}/check', 'check')->where('id','[0-9]+');
    });

    Route::controller(PaymentAssetController::class)->prefix('payment_assets')->group(function () {
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::get('{id}/active/update', 'active_update')->where('id','[0-9]+');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
    });
    // Управление заказами
    Route::controller(OrderController::class)->prefix('orders')->group(function () {
        Route::post('payment', 'payment');
        Route::get('all', 'all');
        Route::get('sales/top', 'salesTop');
        Route::get('{id}/get', 'get')->where('id','[0-9]+');
        Route::get('{id}/download', 'download')->where('id','[0-9]+');
        Route::get('{id}/cancel', 'cancel_admin')->where('id','[0-9]+');
    });

    Route::controller(WithdrawalController::class)->prefix('withdrawals')->group(function () {
        Route::get('all', 'all');
        Route::post('{id}/confirm', 'confirm')->where('id','[0-9]+');
    });

    // Управление магазином
    Route::controller(ShopController::class)->prefix('shops')->group(function () {
        Route::post('token/change', 'update_token');
        Route::get('status/change', 'setStatus');
        Route::get('info', 'info');
        Route::get('token', 'get_token');
    });

    // Настройки магазина
    Route::controller(ShopSettingsController::class)->prefix('shops/settings')->group(function () {
        Route::get('notify', 'info_notify');
        Route::get('display', 'info_display');
        Route::get('referral', 'info_refferal');
        Route::get('topup', 'info_topup');
        Route::post('display/save', 'update_display');
        Route::post('notify/save', 'update_notify');
        Route::get('status-broadcast', 'info_status_broadcast');
        Route::post('status-broadcast/save', 'update_status_broadcast');
        Route::post('status-broadcast/upload-image', 'upload_status_broadcast_image');
        Route::post('referral/save', 'update_referral');
        Route::post('topup/save', 'update_topup');
        Route::get('buttons', 'info_buttons');
        Route::post('buttons/save', 'update_buttons');
        Route::get('policy', 'info_policy');
        Route::post('policy/save', 'update_policy');
        Route::get('delivery-text', 'info_delivery_text');
        Route::post('delivery-text/save', 'update_delivery_text');
        Route::get('support', 'info_support');
        Route::post('support/save', 'update_support');
    });

    // Элементы страницы "О нас"
    Route::controller(AboutItemController::class)->prefix('about-items')->group(function () {
        Route::get('all', 'all');
        Route::post('create', 'create');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
    });

    // Управление загрузками
    Route::controller(AttachmentController::class)->prefix('attachments')->group(function () {
        Route::post('txt/upload', 'uploadTxt');
        Route::post('json/upload', 'uploadJson');
        Route::post('files/upload', 'uploadFiles');
        Route::delete('{id}/delete', 'delete')->where('id','[a-zA-Z0-9]+');
        Route::get('all', 'all');
    });

    // Управление материалами
    Route::controller(MaterialController::class)->prefix('materials')->group(function () {
        Route::post('add', 'add');
        Route::post('export', 'export');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::get('all', 'all');
//        Route::get('all_trash', 'all_trash');
    });

    // Управление экспортом товаров
    Route::controller(MaterialsExportController::class)->prefix('mexports')->group(function () {
        Route::get('{id}/info', 'info')->where('id','[0-9]+');
        Route::get('{id}/download', 'download')->where('id','[0-9]+');
        Route::get('all', 'all');
    });

    // Управление статистикой
    Route::controller(StatController::class)->prefix('stats')->group(function () {
        Route::post('get', 'get');
        Route::get('counter', 'get_counter');
    });

    // Управление вопрос-ответ
    Route::controller(FaqController::class)->prefix('faq')->group(function () {
        Route::get('all', 'all');
        Route::post('create', 'create');
        Route::post('sort', 'sort');
        Route::post('{id}/visibility', 'visibility')->where('id','[0-9]+');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
    });

    // Управление отзывами
    Route::controller(ReviewController::class)->prefix('reviews')->group(function () {
        Route::get('all', 'all');
        Route::post('create', 'create');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
    });

    // Управление статусами
    Route::controller(StatusCheatController::class)->prefix('statuses')->group(function () {
        Route::get('all', 'all');
        Route::get('select/all', 'select_all');
        Route::post('create', 'create');
        Route::get('games/all', 'games_all');
        Route::post('upload-image', 'uploadImage');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
    });

    // Telegram каналы для публикаций
    Route::controller(\App\Http\Controllers\TelegramChannelController::class)
        ->prefix('telegram-channels')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::post('{id}/update', 'update')->where('id','[0-9]+');
            Route::post('{id}/toggle', 'toggle')->where('id','[0-9]+');
            Route::delete('{id}', 'destroy')->where('id','[0-9]+');
        });

    // Сервисное обслуживание: сброс аналитики, массовые очистки, аудит-лог
    Route::controller(\App\Http\Controllers\MaintenanceController::class)
        ->prefix('maintenance')
        ->group(function () {
            Route::get('stats', 'stats');
            Route::get('log', 'auditLog');
            Route::post('analytics/reset', 'resetAnalytics');
            Route::post('cleanup/orders',  'cleanupOrders');
            Route::post('cleanup/coupons', 'cleanupCoupons');
            Route::post('cleanup/files',   'cleanupFiles');
            Route::post('cleanup/exports', 'cleanupExports');
        });

    // Email-рассылки: CRUD черновиков, запуск, прогресс
    Route::controller(\App\Http\Controllers\EmailBroadcastController::class)
        ->prefix('email-broadcasts')
        ->group(function () {
            Route::get('/',           'index');
            Route::get('audience',    'audience');
            Route::post('preview',    'preview');
            Route::post('/',          'store');
            Route::get('{id}',        'show')->where('id','[0-9]+');
            Route::post('{id}/update','update')->where('id','[0-9]+');
            Route::post('{id}/send',  'send')->where('id','[0-9]+');
            Route::delete('{id}',     'destroy')->where('id','[0-9]+');
        });

    // Принудительный сброс пароля (admin endpoints)
    Route::controller(\App\Http\Controllers\PasswordResetController::class)->group(function () {
        Route::post('members/{id}/force-password-reset',        'forceForMember')->where('id','[0-9]+');
        Route::post('members/{id}/force-password-reset/cancel', 'cancelForMember')->where('id','[0-9]+');
        Route::post('admins/{id}/force-password-reset',         'forceForAdmin')->where('id','[0-9]+');
    });

    // Управление инструкциями
    Route::controller(InstructionController::class)->prefix('instructions')->group(function () {
        Route::get('all', 'all');
        Route::get('list', 'list');
        Route::post('create', 'create');
        Route::post('alias/check', 'check_alias');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
    });

    // Управление категориями
    Route::controller(CategoryController::class)->prefix('categories')->group(function () {
        Route::post('create', 'create');
        Route::post('sort', 'sort');
        Route::get('all', 'all');
        Route::get('select/all', 'selectAll');
        Route::get('{type}/all', 'list_cats');
        Route::post('{id}/visibility', 'visibility')->where('id','[0-9]+');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::get('{id}/info', 'info')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
    });

    // Управление каналами
    Route::controller(ChannelSubController::class)->prefix('channels/sub')->group(function () {
        Route::post('create', 'create');
        Route::post('sort', 'sort');
        Route::post('{cid}/update', 'update')->where('cid','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::get('all', 'all');
    });

    // Управление рекламными ссылками
    Route::controller(LinkAdsController::class)->prefix('links')->group(function () {
        Route::post('create', 'create');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::get('all', 'all');
    });

    // Управление ролями
    Route::controller(RoleController::class)->prefix('roles')->group(function () {
        Route::post('create', 'create');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::get('{id}/info', 'info')->where('id','[0-9]+');
        Route::get('all', 'all');
        Route::get('select/all', 'select_all');
    });

    // Управление разрешениями
    Route::controller(RolePermissionController::class)->prefix('permissions')->group(function () {
        Route::get('{id}/list', 'list_by_id')->where('id','[0-9]+');
        Route::get('{alias}/check', 'check_role')->where('alias','[a-z_]+');
        Route::post('{role_id}/{permission}/update', 'update')->where('role_id','[0-9]+')->where('permission','[a-z_.]+');
        Route::post('{role_id}/{permission}/like/update', 'like_update')->where('role_id','[0-9]+')->where('permission','[a-z_.]+');
    });

    // Управление кнопками
    Route::controller(ButtonController::class)->prefix('buttons')->group(function () {
        Route::post('create', 'create');
        Route::post('sort', 'sort');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::get('all', 'all');
    });

    // Управление настройкой кнопок
    Route::controller(ButtonSetController::class)->prefix('buttons/set')->group(function () {
        Route::post('update', 'update');
    });

    // Управление принудительной подпиской
    Route::controller(ChannelSubSettingsController::class)->prefix('channels/sub/settings')->group(function () {
        Route::get('button_check/info', 'info_button_check');
        Route::post('active', 'set_active');
        Route::post('update', 'update');
        Route::post('button_check/update', 'update_button_check');
    });

    // Управление купонами
    Route::controller(CouponController::class)->prefix('coupons')->group(function () {
        Route::post('create', 'create');
        Route::post('update', 'update');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::get('all', 'all');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
    });

    // Управление товарами
    Route::controller(ProductController::class)->prefix('products')->group(function () {
        Route::post('create', 'create');
        Route::post('sort', 'sort');
        Route::post('{id}/visibility', 'visibility')->where('id','[0-9]+');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::get('{id}/info', 'info')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::post('alias/check', 'check_alias');
        Route::get('all', 'all');
        Route::get('select/all', 'selectAll');
        Route::post('upload-video', 'uploadVideo');
    });

    // Управление рассылками
    Route::controller(SenderController::class)->prefix('senders')->group(function () {
        Route::post('create', 'create');
        Route::post('check', 'check');
        Route::post('{id}/update', 'update')->where('id','[0-9]+');
        Route::delete('{id}/delete', 'delete')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::get('all', 'all');
    });

    // Управление текстами
    Route::controller(TextController::class)->prefix('texts')->group(function () {
        Route::get('fullinfo', 'fullinfo');
        Route::post('{type}/update', 'update')->where('type','[a-z_]+');
        Route::post('{type}/active', 'set_active')->where('type','[a-z_]+');
    });

    // Управление способами оплаты
    Route::controller(MethodPaymentController::class)->prefix('methods_payments')->group(function () {
        Route::post('create', 'create');
        Route::post('update', 'update');
        Route::post('sort', 'sort');
        Route::post('visibility', 'visibility');
        Route::post('delete', 'delete');
        Route::get('{type}/info', 'info')->where('type','[a-z]+');
        Route::get('{type}/exists', 'exists')->where('type','[a-z]+');
        Route::get('{type}/fullinfo', 'fullinfo')->where('type','[a-z]+');
        Route::get('all', 'all');
        Route::get('select/all', 'selectAll');
        Route::post('ps/admin/all', 'psAllAdmin');
    });

    // Управление пользователями
    Route::controller(MemberController::class)->prefix('members')->group(function () {
        Route::get('all', 'all');
//        Route::get('test', 'list_test');
        Route::post('write', 'write');
        Route::post('delete', 'delete');
        Route::post('ban', 'ban');
        Route::post('import', 'import');
        Route::post('change/ref_percent', 'update_ref');
        Route::post('change/role', 'update_role');
        Route::post('change/balance', 'update_balance');
        Route::post('change/telegram/unlink', 'unlink_telegram');
        Route::get('list', 'list');
        Route::get('{id}/info', 'info')->where('id','[0-9]+');
        Route::get('{id}/fullinfo', 'fullinfo')->where('id','[0-9]+');
        Route::get('{type}/export', 'export')->where('type','[a-z]+');
    });

    // Тикеты (disabled)
    // Route::controller(TicketController::class)->group(function () {
    //     Route::prefix('tickets')->group(function () {
    //         Route::get('all', 'all');
    //         Route::get('{id}/fullinfo', 'fullinfo');
    //         Route::get('{id}/messages', 'messages');
    //         Route::post('{id}/create', 'messages_create');
    //         Route::post('{id}/close', 'close');
    //         Route::post('{id}/blocked', 'blocked_member');
    //         Route::get('{id}/last/messages', 'last_messages');
    //     })->where('id','[0-9]+')->where('type','[0-9]+');
    // });
});
Route::get('login', function (){
    return response()->json(['ok' => false, 'description' => 'Unauthenticated'], 401);
})->name('login');
// Для авторизованных
Route::middleware('auth')->group(function () {

    Route::controller(OrderController::class)->prefix('orders')->group(function () {
        Route::post('payment_link/get', 'get_payment_link');
        Route::post('balance/create', 'create_topup');
        Route::get('{id}/info', 'info_by_id')->where('id','[0-9]+');
        Route::get('{id}/check', 'check')->where('id','[a-zA-Z0-9]+');
    });


    Route::controller(AuthController::class)->prefix('auth')->group(function () {
        Route::post('logout', 'logout');
        Route::post('refresh', 'refresh');
    });


    // Проверка купонов
    Route::controller(CouponController::class)->prefix('coupons')->group(function () {
        Route::post('check', 'check');
    });

    // Пользователь
    Route::controller(MemberController::class)->prefix('members')->group(function () {
        Route::post('change/login', 'change_login');
        Route::post('change/email', 'change_email');
        Route::post('change/password', 'change_password');
        // Route::post('change/notify/tickets', 'change_notify_tickets');
        Route::post('change/notify/orders', 'change_notify_orders');
        Route::post('change/notify/status_changed', 'change_notify_status_changed');
        Route::get('orders', 'orders');
        Route::get('refills', 'refills');
        Route::get('withdraw','withdraw');
    });

    // Выводы
    Route::controller(WithdrawalController::class)->prefix('withdrawals')->group(function () {
        Route::post('create', 'create');
    });

    // Тикеты (disabled)
    // Route::controller(TicketController::class)->prefix('tickets')->group(function () {
    //     Route::post('create', 'create');
    //     Route::get('list', 'list');
    //     Route::get('subjects', 'list_subjects');
    //     Route::get('{id}/info', 'info')->where('id', '[0-9]+');
    //     Route::post('{id}/create', 'messages_create')->where('id', '[0-9]+');
    //     Route::post('{id}/close', 'close')->where('id', '[0-9]+');
    // });

    // Тарифы
    Route::controller(TariffController::class)->prefix('tariffs')->group(function () {
        Route::get('{id}/list', 'list_by_id')->where('id','[0-9]+');
    });
});

// Для гостей
Route::middleware('guest')->middleware('api_request_limit')->group(function () {

    Route::controller(AuthController::class)->prefix('auth')->group(function () {
        Route::post('login', 'login')->middleware('login.throttle');
        Route::post('register', 'register');
        Route::post('reset-password', 'reset_password');
    });
});

// 2FA — admin scope (auth + admin role); 2FA verify itself does NOT require
// the '2fa' middleware (otherwise the user could never pass it).
Route::middleware(['auth', 'admin'])->prefix('admin/2fa')->group(function () {
    Route::get('setup',   [\App\Http\Controllers\TwoFactorController::class, 'setup']);
    Route::post('enable', [\App\Http\Controllers\TwoFactorController::class, 'enable']);
    Route::post('verify', [\App\Http\Controllers\TwoFactorController::class, 'verify']);
    Route::post('disable',[\App\Http\Controllers\TwoFactorController::class, 'disable']);
    // Email-based recovery for the case when the authenticator device is lost.
    Route::post('recovery/email/request', [\App\Http\Controllers\TwoFactorController::class, 'requestEmailRecovery']);
    Route::post('recovery/email/confirm', [\App\Http\Controllers\TwoFactorController::class, 'confirmEmailRecovery']);
});

Route::controller(AttachmentController::class)->prefix('attachments')->group(function () {
    Route::post('image/upload', 'uploadImage');
});

Route::controller(MethodPaymentController::class)->prefix('methods_payments')->group(function () {
    Route::post('ps/all', 'psAll');
    Route::get('{id}/list', 'psAllByPsid')->where('id','[0-9]+');
});

Route::controller(ProductController::class)->prefix('products')->group(function () {
    Route::get('{id}/buy/info', 'info_buy')->where('id','[0-9]+');
    Route::get('{alias}/tariffs', 'info_by_alias')->where('alias','[a-zA-Z0-9_-]+');
});
Route::controller(OrderController::class)->prefix('orders')->group(function () {
    Route::post('create', 'create_site');
    Route::get('{hash}/info', 'info')->where('hash', '[a-zA-Z0-9]+');
    Route::post('{hash}/cancel', 'cancel')->where('hash', '[a-zA-Z0-9]+');
});
Route::controller(AuthController::class)->prefix('users')->group(function () {
    Route::get('info', 'info');
});

});


