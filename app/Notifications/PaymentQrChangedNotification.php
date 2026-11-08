<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentQrChangedNotification extends Notification
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
            'kind' => 'payment_qr_changed',
            'title' => 'QRIS kedai diperbarui',
            'message' => "QRIS {$this->order->shop_name} diperbarui. Periksa kembali halaman pesanan sebelum membayar.",
            'business_name' => $this->order->shop_name,
            'url' => route('orders.show', $this->order->public_code),
        ];
    }
}
