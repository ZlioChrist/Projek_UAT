<?php

namespace App\Filament\Widgets;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Models\Order;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOverview extends Widget
{
    protected static string $view = 'filament.widgets.shop-overview';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Admin, UserRole::Roastery], true);
    }

    protected function getViewData(): array
    {
        $user = AuthenticatedUser::require();
        $role = UserRole::fromUserValue($user?->role);

        $cafes = $role === UserRole::Admin
            ? $user->cafes()
                ->with(['menuItems' => fn (HasMany $query) => $query->orderBy('name'), 'facilities'])
                ->orderBy('name')
                ->get()
            : collect();

        $roasteries = $role === UserRole::Roastery
            ? $user->roasteries()
                ->with(['menuItems' => fn (HasMany $query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get()
            : collect();

        $orders = Order::query()
            ->with(['cafe', 'roastery', 'items'])
            ->when($role === UserRole::Admin, fn (Builder $query) => $query->whereIn('cafe_id', $user->cafes()->select('id')))
            ->when($role === UserRole::Roastery, fn (Builder $query) => $query->whereIn('roastery_id', $user->roasteries()->select('id')))
            ->latest()
            ->limit(8)
            ->get();

        return compact('cafes', 'roasteries', 'orders');
    }
}
