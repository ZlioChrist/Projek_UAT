<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;

class BestSellingProductsChart extends OrderTrendChart
{
    protected static ?string $heading = 'Produk Terlaris';

    protected static ?string $description = 'Produk utama dengan jumlah terjual terbanyak dalam 30 hari terakhir.';

    protected static string $color = 'success';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = now()->startOfDay()->subDays(29);
        $orders = $this->getScopedOrdersQuery()
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->whereBetween('orders.created_at', [$start, now()->endOfDay()]);

        $items = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.id', $orders->select('orders.id'))
            ->whereNull('order_items.parent_order_item_id')
            ->selectRaw('order_items.name_snapshot as product_name, SUM(order_items.quantity) as quantity_sold')
            ->groupBy('order_items.name_snapshot')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah terjual',
                    'data' => $items->pluck('quantity_sold')->map(fn ($quantity): int => (int) $quantity)->all(),
                    'backgroundColor' => self::COLOR_PRIMARY,
                ],
            ],
            'labels' => $items->pluck('product_name')->all(),
        ];
    }
}
