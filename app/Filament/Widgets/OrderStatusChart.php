<?php

namespace App\Filament\Widgets;

use App\Models\Order;

class OrderStatusChart extends OrderTrendChart
{
    protected static ?string $heading = 'Status Pesanan';

    protected static ?string $description = 'Bandingkan pesanan dari tahap baru masuk hingga selesai atau dibatalkan.';

    protected static string $color = 'warning';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = $this->getScopedOrdersQuery()
            ->selectRaw('status, COUNT(*) as value')
            ->groupBy('status')
            ->pluck('value', 'status');

        return [
            'datasets' => [
                [
                    'label' => 'Pesanan',
                    'data' => [
                        (int) $counts->only([
                            Order::STATUS_AWAITING_PAYMENT,
                            Order::STATUS_PENDING_VERIFICATION,
                            Order::STATUS_PAID,
                        ])->sum(),
                        (int) $counts->only([
                            Order::STATUS_PREPARING,
                            Order::STATUS_DELIVERING,
                            Order::STATUS_AWAITING_RECEIPT_CONFIRMATION,
                        ])->sum(),
                        (int) $counts->get(Order::STATUS_READY, 0),
                        (int) $counts->get(Order::STATUS_COMPLETED, 0),
                        (int) $counts->get(Order::STATUS_CANCELLED, 0),
                    ],
                    'backgroundColor' => [
                        self::COLOR_PRIMARY,
                        self::COLOR_ACCENT,
                        self::COLOR_SUCCESS,
                        self::COLOR_MUTED,
                        '#b85c50',
                    ],
                ],
            ],
            'labels' => ['Baru masuk', 'Sedang diproses', 'Siap diambil', 'Selesai', 'Dibatalkan'],
        ];
    }
}
