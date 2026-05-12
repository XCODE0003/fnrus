<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\FilamentSiteAuthBridge;
use App\Http\Middleware\FilamentTwoFactorChallenge;
use App\Http\Middleware\VerifyCsrfToken;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(env('FILAMENT_PATH', 'xoalfjamapfn/admin'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                'Продажи',
                'Контент',
                'Маркетинг',
                'Финансы',
                'Пользователи',
                'Обслуживание',
                'Настройки',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                FilamentSiteAuthBridge::class,
                FilamentTwoFactorChallenge::class,
            ], isPersistent: true);
    }
}
