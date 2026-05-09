<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Smoke test: verifies that critical admin routes are registered and
 * point at the expected controller@method. Catches the class of bugs
 * where a route file refactor silently drops or renames an endpoint.
 */
class RoutesRegistrationTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function routeProvider(): array
    {
        return [
            'statuses upload-image' => [
                'POST', 'api/statuses/upload-image',
                'App\\Http\\Controllers\\StatusCheatController@uploadImage',
            ],
            'statuses fullinfo' => [
                'GET', 'api/statuses/{id}/fullinfo',
                'App\\Http\\Controllers\\StatusCheatController@fullinfo',
            ],
            'statuses update' => [
                'POST', 'api/statuses/{id}/update',
                'App\\Http\\Controllers\\StatusCheatController@update',
            ],
            'telegram-channels index' => [
                'GET', 'api/telegram-channels',
                'App\\Http\\Controllers\\TelegramChannelController@index',
            ],
            'telegram-channels store' => [
                'POST', 'api/telegram-channels',
                'App\\Http\\Controllers\\TelegramChannelController@store',
            ],
            'telegram-channels toggle' => [
                'POST', 'api/telegram-channels/{id}/toggle',
                'App\\Http\\Controllers\\TelegramChannelController@toggle',
            ],
            'telegram-channels destroy' => [
                'DELETE', 'api/telegram-channels/{id}',
                'App\\Http\\Controllers\\TelegramChannelController@destroy',
            ],
            'maintenance stats' => [
                'GET', 'api/maintenance/stats',
                'App\\Http\\Controllers\\MaintenanceController@stats',
            ],
            'maintenance log' => [
                'GET', 'api/maintenance/log',
                'App\\Http\\Controllers\\MaintenanceController@auditLog',
            ],
            'maintenance analytics reset' => [
                'POST', 'api/maintenance/analytics/reset',
                'App\\Http\\Controllers\\MaintenanceController@resetAnalytics',
            ],
            'maintenance cleanup orders' => [
                'POST', 'api/maintenance/cleanup/orders',
                'App\\Http\\Controllers\\MaintenanceController@cleanupOrders',
            ],
            'maintenance cleanup coupons' => [
                'POST', 'api/maintenance/cleanup/coupons',
                'App\\Http\\Controllers\\MaintenanceController@cleanupCoupons',
            ],
            'maintenance cleanup files' => [
                'POST', 'api/maintenance/cleanup/files',
                'App\\Http\\Controllers\\MaintenanceController@cleanupFiles',
            ],
            'maintenance cleanup exports' => [
                'POST', 'api/maintenance/cleanup/exports',
                'App\\Http\\Controllers\\MaintenanceController@cleanupExports',
            ],
            'email-broadcasts index' => [
                'GET', 'api/email-broadcasts',
                'App\\Http\\Controllers\\EmailBroadcastController@index',
            ],
            'email-broadcasts audience' => [
                'GET', 'api/email-broadcasts/audience',
                'App\\Http\\Controllers\\EmailBroadcastController@audience',
            ],
            'email-broadcasts preview' => [
                'POST', 'api/email-broadcasts/preview',
                'App\\Http\\Controllers\\EmailBroadcastController@preview',
            ],
            'email-broadcasts store' => [
                'POST', 'api/email-broadcasts',
                'App\\Http\\Controllers\\EmailBroadcastController@store',
            ],
            'email-broadcasts show' => [
                'GET', 'api/email-broadcasts/{id}',
                'App\\Http\\Controllers\\EmailBroadcastController@show',
            ],
            'email-broadcasts update' => [
                'POST', 'api/email-broadcasts/{id}/update',
                'App\\Http\\Controllers\\EmailBroadcastController@update',
            ],
            'email-broadcasts send' => [
                'POST', 'api/email-broadcasts/{id}/send',
                'App\\Http\\Controllers\\EmailBroadcastController@send',
            ],
            'email-broadcasts destroy' => [
                'DELETE', 'api/email-broadcasts/{id}',
                'App\\Http\\Controllers\\EmailBroadcastController@destroy',
            ],
            'public unsubscribe' => [
                'GET', 'unsubscribe/{token}',
                'App\\Http\\Controllers\\UnsubscribeController@unsubscribe',
            ],
            'force reset member' => [
                'POST', 'api/members/{id}/force-password-reset',
                'App\\Http\\Controllers\\PasswordResetController@forceForMember',
            ],
            'cancel reset member' => [
                'POST', 'api/members/{id}/force-password-reset/cancel',
                'App\\Http\\Controllers\\PasswordResetController@cancelForMember',
            ],
            'force reset admin' => [
                'POST', 'api/admins/{id}/force-password-reset',
                'App\\Http\\Controllers\\PasswordResetController@forceForAdmin',
            ],
            'public password reset complete' => [
                'POST', 'api/password-reset/{token}',
                'App\\Http\\Controllers\\PasswordResetController@complete',
            ],
        ];
    }

    /**
     * @dataProvider routeProvider
     */
    public function test_route_is_registered(string $method, string $uri, string $action): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($r) => in_array($method, $r->methods(), true) && $r->uri() === $uri
        );

        $this->assertNotNull($route, "Route {$method} {$uri} is not registered");
        $this->assertSame($action, $route->getActionName());
    }

    public function test_admin_routes_are_behind_admin_middleware(): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($r) => in_array('POST', $r->methods(), true) && $r->uri() === 'api/telegram-channels'
        );

        $this->assertNotNull($route);
        $this->assertContains('admin', $route->gatherMiddleware());
        $this->assertContains('2fa', $route->gatherMiddleware());
    }
}
