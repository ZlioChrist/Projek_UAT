<?php

namespace App\Filament\Widgets;

class OrderVolumeChart extends OrderTrendChart
{
    protected static ?string $heading = 'Tren Pesanan';

    protected static ?string $description = 'Jumlah pesanan yang masuk per hari selama 7 hari terakhir.';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = $this->getDateSeries();
        $start = now()->startOfDay()->subDays(6);

        $rows = $this->getScopedOrdersQuery()
            ->whereBetween('created_at', [$start, now()->endOfDay()])
            ->selectRaw('DATE(created_at) as trend_date, COUNT(*) as value')
            ->groupByRaw('DATE(created_at)')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Pesanan',
                    'data' => $this->mapDailyValues($rows, $series['keys']),
                    'borderColor' => self::COLOR_PRIMARY,
                    'backgroundColor' => 'rgba(192, 138, 91, 0.18)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }
}
