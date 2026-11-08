<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderReadyNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'order_ready',
            'title' => 'Pesanan siap diambil',
            'message' => "Pesanan {$this->order->public_code} dari {$this->order->shop_name} sudah siap diambil.",
            'business_name' => $this->order->shop_name,
            'url' => route('orders.show', $this->order->public_code),
        ];
    }
}
