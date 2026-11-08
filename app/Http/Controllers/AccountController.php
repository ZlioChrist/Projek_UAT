<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $cart = $request->session()->get('order_cart', ['items' => []]);
        $cartCount = collect($cart['items'] ?? [])->sum(fn ($quantity) => max(0, (int) $quantity));
        $savedCount = $request->user()->bookmarks()->count();
        $orderCount = $request->user()->orders()->count();
        $activeOrderCount = $request->user()->orders()
            ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])
            ->count();
        $unreadNotificationCount = $request->user()->unreadNotifications()->count();
        $profilePhotoUrl = $request->user()->profile_photo_path
            ? Storage::disk('public')->url($request->user()->profile_photo_path)
            : null;

        return view('account.index', compact('cartCount', 'savedCount', 'orderCount', 'activeOrderCount', 'unreadNotificationCount', 'profilePhotoUrl'));
    }
}
