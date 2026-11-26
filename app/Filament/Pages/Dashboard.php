<?php

namespace App\Filament\Pages;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Widgets\BestSellingProductsChart;
use App\Filament\Widgets\OrderStatusChart;
use App\Filament\Widgets\OrderVolumeChart;
use App\Filament\Widgets\RevenueTrendChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\WelcomeBanner;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public static function shouldRegisterNavigation(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [
            UserRole::Developer,
            UserRole::Admin,
            UserRole::Roastery,
        ], true);
    }

    public static function getNavigationLabel(): string
    {
        return 'Dasbor';
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public function getWidgets(): array
    {
        return [
            WelcomeBanner::class,
            StatsOverview::class,
            OrderVolumeChart::class,
            RevenueTrendChart::class,
            OrderStatusChart::class,
            BestSellingProductsChart::class,
        ];
    }
}
