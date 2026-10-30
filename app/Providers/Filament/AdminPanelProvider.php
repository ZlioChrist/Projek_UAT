<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\CustomLogin::class)
            ->brandName('WadahNgopi')
            ->brandLogo(asset('wadahngopi.png'))
            ->brandLogoHeight('3.5rem')
            ->favicon(asset('wadahngopi.png'))
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Stone,
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'info' => Color::Sky,
            ])
            ->font('Outfit')
            ->renderHook(
                'panels::styles.after',
                fn () => new \Illuminate\Support\HtmlString('
                    <link rel="stylesheet" href="'.asset('css/filament-custom.css').'?v='.filemtime(public_path('css/filament-custom.css')).'">
                    <meta name="theme-color" content="#060606">
                    <meta name="referrer" content="strict-origin-when-cross-origin">
                    <style>
                        :root {
                            --font-family: "Outfit", sans-serif;
                        }
                    </style>
                '),
            )
            ->renderHook(
                'panels::head.start',
                fn () => new \Illuminate\Support\HtmlString('
                    <meta http-equiv="X-Frame-Options" content="DENY">
                    <meta http-equiv="X-Content-Type-Options" content="nosniff">
                    <meta http-equiv="Permissions-Policy" content="camera=(), microphone=(), geolocation=(self)">
                '),
            )
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\WelcomeBanner::class,
                \App\Filament\Widgets\StatsOverview::class,
            ])
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
                \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
                \App\Http\Middleware\SecurityHeaders::class,
            ])
            ->authMiddleware([
                \App\Http\Middleware\RedirectUserFromAdmin::class,
                Authenticate::class,
            ]);
    }
}
