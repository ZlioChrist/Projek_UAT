<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $orders = Order::with(['cafe', 'roastery', 'items'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(5)
            ->get();
        $cart = $request->session()->get('order_cart', ['items' => []]);
        $cartCount = collect($cart['items'] ?? [])->sum(fn ($quantity) => max(0, (int) $quantity));
        $savedCount = $request->user()->bookmarks()->count();
        $orderCount = $request->user()->orders()->count();
        $activeOrderCount = $request->user()->orders()
            ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])
            ->count();

        return view('account.index', compact('orders', 'cartCount', 'savedCount', 'orderCount', 'activeOrderCount'));
    }
}
