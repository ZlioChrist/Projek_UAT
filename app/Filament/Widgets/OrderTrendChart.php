<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Order;
use App\Support\AuthenticatedUser;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

abstract class OrderTrendChart extends ChartWidget
{
    protected const COLOR_PRIMARY = '#c08a5b';

    protected const COLOR_ACCENT = '#e9b65b';

    protected const COLOR_SUCCESS = '#6f9e78';

    protected const COLOR_MUTED = '#8b7663';

    protected static ?string $pollingInterval = '60s';

    protected static ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 1;

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => true,
            'animation' => ['duration' => 300],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'color' => '#c0aa95',
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'boxWidth' => 8,
                        'boxHeight' => 8,
                        'padding' => 18,
                        'font' => ['family' => 'Outfit', 'size' => 12],
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => '#2b2119',
                    'titleColor' => '#fffaf4',
                    'bodyColor' => '#f3e8dc',
                    'borderColor' => '#594635',
                    'borderWidth' => 1,
                    'padding' => 10,
                    'cornerRadius' => 8,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => [
                        'color' => '#c0aa95',
                        'font' => ['family' => 'Outfit'],
                    ],
                    'border' => ['display' => false],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(192, 138, 91, 0.12)'],
                    'ticks' => [
                        'color' => '#c0aa95',
                        'precision' => 0,
                        'font' => ['family' => 'Outfit'],
                    ],
                    'border' => ['display' => false],
                ],
            ],
            'elements' => [
                'line' => ['borderWidth' => 3, 'tension' => 0.35],
                'point' => ['radius' => 3, 'hoverRadius' => 5, 'hitRadius' => 8],
                'bar' => ['borderRadius' => 6],
                'arc' => ['borderWidth' => 3, 'borderColor' => '#241c16'],
            ],
        ];
    }

    protected function getScopedOrdersQuery(): Builder
    {
        $user = AuthenticatedUser::get();
        $query = Order::query();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return match (UserRole::fromUserValue($user->role)) {
            UserRole::Admin => $query->whereIn('cafe_id', $user->cafes()->select('id')),
            UserRole::Roastery => $query->whereIn('roastery_id', $user->roasteries()->select('id')),
            UserRole::Developer => $query,
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @return array{keys: array<int, string>, labels: array<int, string>}
     */
    protected function getDateSeries(): array
    {
        $keys = [];
        $labels = [];
        $date = now()->startOfDay()->subDays(6);

        for ($day = 0; $day < 7; $day++) {
            $keys[] = $date->toDateString();
            $labels[] = $date->format('d/m');
            $date->addDay();
        }

        return ['keys' => $keys, 'labels' => $labels];
    }

    /**
     * @param  iterable<object{trend_date: string, value: int|float|string}>  $rows
     * @return array<int, int|float>
     */
    protected function mapDailyValues(iterable $rows, array $dateKeys): array
    {
        $valuesByDate = array_fill_keys($dateKeys, 0);

        foreach ($rows as $row) {
            $dateKey = (string) $row->trend_date;

            if (array_key_exists($dateKey, $valuesByDate)) {
                $valuesByDate[$dateKey] = is_numeric($row->value) ? 0 + $row->value : 0;
            }
        }

        return array_values($valuesByDate);
    }
}
