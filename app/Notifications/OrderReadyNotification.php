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
        [$title, $message] = match ($this->order->status) {
            Order::STATUS_DELIVERING => [
                'Pesanan sedang dikirim',
                "Pesanan {$this->order->display_code} dari {$this->order->shop_name} sedang dikirim oleh kurir.",
            ],
            Order::STATUS_AWAITING_RECEIPT_CONFIRMATION => [
                'Konfirmasi penerimaan pesanan',
                "Pesanan {$this->order->display_code} telah diserahkan. Konfirmasikan penerimaan di halaman pesanan.",
            ],
            default => $this->order->fulfillment_method === 'delivery'
                ? ['Pesanan siap diantar', "Pesanan {$this->order->display_code} dari {$this->order->shop_name} siap untuk diantarkan."]
                : ['Pesanan siap diambil', "Pesanan {$this->order->display_code} dari {$this->order->shop_name} sudah siap diambil."],
        };

        return [
            'kind' => 'order_ready',
            'title' => $title,
            'message' => $message,
            'business_name' => $this->order->shop_name,
            'url' => route('orders.show', $this->order->public_code),
        ];
    }
}
