<?php

namespace App\Providers\Filament;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Pages\AiOptimization;
use App\Filament\Pages\Auth\CustomLogin;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ProfileSettings;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\WelcomeBanner;
use App\Http\Middleware\SecurityHeaders;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(CustomLogin::class)
            ->authGuard('web')
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
                fn () => new HtmlString('
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
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups([
                'Manajemen Kafe',
                'Manajemen Roastery',
                'Fitur Tambahan',
                'Manajemen User',
                'Akun',
                'Manajemen Konten',
                'Kendali Admin',
            ])
            ->navigationItems([
                NavigationItem::make('Optimalisasi AI')
                    ->url(fn (): string => AiOptimization::getUrl())
                    ->icon('heroicon-o-sparkles')
                    ->group('Fitur Tambahan')
                    ->sort(1)
                    ->visible(fn (): bool => in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Admin, UserRole::Roastery], true)),
                NavigationItem::make('Kelola User')
                    ->url(fn (): string => ProfileSettings::getUrl())
                    ->icon('heroicon-o-user-circle')
                    ->group('Manajemen User')
                    ->sort(1)
                    ->visible(fn (): bool => in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Admin, UserRole::Roastery], true)),
                NavigationItem::make('Notifikasi Pelanggan')
                    ->url(fn (): string => route('notifications.index'))
                    ->icon('heroicon-o-bell')
                    ->group('Akun')
                    ->badge(fn (): ?string => AuthenticatedUser::get()?->unreadNotifications()->count() ?: null, 'warning')
                    ->visible(fn (): bool => UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::User),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                WelcomeBanner::class,
                StatsOverview::class,
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
                ThrottleRequests::class.':60,1',
                SecurityHeaders::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
