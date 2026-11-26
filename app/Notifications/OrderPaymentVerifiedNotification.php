<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPaymentVerifiedNotification extends Notification
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
            'kind' => 'order_payment_verified',
            'title' => 'Pembayaran terverifikasi',
            'message' => "Pembayaran pesanan {$this->order->display_code} dari {$this->order->shop_name} telah diverifikasi dan pesanan masuk daftar kedai.",
            'business_name' => $this->order->shop_name,
            'order_code' => $this->order->public_code,
            'url' => route('orders.show', $this->order->public_code, false),
        ];
    }
}
