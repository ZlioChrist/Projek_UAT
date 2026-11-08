<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\Order;
use App\Models\Roastery;
use App\Models\User;
use App\Notifications\PaymentQrChangedNotification;

class PaymentQrUpdateNotifier
{
    public function notify(Cafe|Roastery $shop): void
    {
        $shopColumn = $shop instanceof Cafe ? 'cafe_id' : 'roastery_id';

        Order::query()
            ->where($shopColumn, $shop->id)
            ->where('payment_method', 'qris')
            ->where('status', Order::STATUS_AWAITING_PAYMENT)
            ->whereNotNull('user_id')
            ->select(['id', 'user_id'])
            ->chunkById(100, function ($orders): void {
                $users = User::query()->whereIn('id', $orders->pluck('user_id')->unique())->get()->keyBy('id');

                foreach ($orders as $order) {
                    $user = $users->get($order->user_id);
                    if ($user) {
                        $user->notify(new PaymentQrChangedNotification($order->load(['cafe', 'roastery'])));
                    }
                }
            });
    }
}
