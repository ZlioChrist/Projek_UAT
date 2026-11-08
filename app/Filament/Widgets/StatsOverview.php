<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\CafeResource;
use App\Filament\Resources\MenuItemResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\RoasteryResource;
use App\Models\Cafe;
use App\Models\MenuItem;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s'; // Slightly slower for better heavy-load management

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }
        $role = UserRole::fromUserValue($user->role);
        $isDeveloper = $role === UserRole::Developer;

        $cafeQuery = Cafe::query();
        if ($role === UserRole::Admin) {
            $cafeQuery->where('owner_id', $user->id);
        } elseif (! $isDeveloper) {
            $cafeQuery->whereRaw('1 = 0');
        }

        if (in_array($role, [UserRole::Admin, UserRole::Roastery], true)) {
            $shopIds = $role === UserRole::Admin
                ? $user->cafes()->select('id')
                : $user->roasteries()->select('id');
            $shopColumn = $role === UserRole::Admin ? 'cafe_id' : 'roastery_id';
            $shopCount = $role === UserRole::Admin ? $cafeQuery->count() : $user->roasteries()->count();
            $menuCount = MenuItem::query()->whereIn($shopColumn, $shopIds)->count();
            $activeOrders = Order::query()
                ->whereIn($shopColumn, $shopIds)
                ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])
                ->count();

            return [
                Stat::make($role === UserRole::Admin ? 'Kafe Saya' : 'Roastery Saya', $shopCount)
                    ->description('Kedai yang dikelola akun ini')
                    ->descriptionIcon($role === UserRole::Admin ? 'heroicon-m-building-storefront' : 'heroicon-m-sparkles')
                    ->color('primary')
                    ->url($role === UserRole::Admin ? CafeResource::getUrl('index') : RoasteryResource::getUrl('index')),
                Stat::make('Menu & Produk', $menuCount)
                    ->description('Menu yang terhubung ke kedai Anda')
                    ->descriptionIcon('heroicon-m-cake')
                    ->color('success')
                    ->url(MenuItemResource::getUrl('index')),
                Stat::make('Pesanan Berjalan', $activeOrders)
                    ->description('Belum selesai dan tidak dibatalkan')
                    ->descriptionIcon('heroicon-m-bell-alert')
                    ->color('warning')
                    ->url(OrderResource::getUrl('index')),
            ];
        }

        return [
            Stat::make('Total Kafe', $cafeQuery->count())
                ->description('Jumlah kafe yang terdaftar')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary')
                ->url(CafeResource::getUrl('index')),
            Stat::make('Menu & Produk', MenuItem::query()->count())
                ->description('Menu dan produk yang dikelola')
                ->descriptionIcon('heroicon-m-cake')
                ->color('success')
                ->url(MenuItemResource::getUrl('index')),
            Stat::make('Pesanan Berjalan', Order::query()
                ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])
                ->count())
                ->description('Belum selesai dan tidak dibatalkan')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color('warning')
                ->url(OrderResource::getUrl('index')),
        ];
    }
}
