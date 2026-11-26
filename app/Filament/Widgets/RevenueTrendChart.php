<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;

class RevenueTrendChart extends OrderTrendChart
{
    protected static ?string $heading = 'Tren Omzet';

    protected static ?string $description = 'Omzet pesanan lunas per hari selama 7 hari terakhir.';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = $this->getDateSeries();
        $start = now()->startOfDay()->subDays(6);

        $rows = $this->getScopedOrdersQuery()
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$start, now()->endOfDay()])
            ->selectRaw('DATE(paid_at) as trend_date, SUM(total) as value')
            ->groupByRaw('DATE(paid_at)')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Omzet (Rp)',
                    'data' => $this->mapDailyValues($rows, $series['keys']),
                    'borderColor' => self::COLOR_ACCENT,
                    'backgroundColor' => 'rgba(233, 182, 91, 0.18)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return array_replace_recursive(parent::getOptions(), [
            'scales' => [
                'y' => [
                    'ticks' => [
                        'callback' => RawJs::make('function(value) { return "Rp " + new Intl.NumberFormat("id-ID").format(value); }'),
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => RawJs::make('function(context) { return "Omzet: Rp " + new Intl.NumberFormat("id-ID").format(context.parsed.y); }'),
                    ],
                ],
            ],
        ]);
    }
}
