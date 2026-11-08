<?php

namespace App\Observers;

use App\Models\MenuItem;
use App\Services\BusinessUpdateNotifier;

class MenuItemObserver
{
    public function created(MenuItem $menuItem): void
    {
        if (! $menuItem->is_active) {
            return;
        }

        $business = $menuItem->cafe ?? $menuItem->roastery;
        if (! $business || $business->status !== 'published') {
            return;
        }

        app(BusinessUpdateNotifier::class)->notify(
            $business,
            'Menu baru tersedia',
            "Menu {$menuItem->name} baru saja ditambahkan ke {$business->name}.",
        );
    }
}
