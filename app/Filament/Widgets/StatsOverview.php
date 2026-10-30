<?php

namespace App\Filament\Widgets;

use App\Models\Cafe;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s'; // Slightly slower for better heavy-load management

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }
        $isDeveloper = $user->role === 'developer';

        $cafeQuery = Cafe::query();
        if (! $isDeveloper) {
            $cafeQuery->where('owner_id', $user->id);
        }

        $stats = [
            Stat::make('Total Kafe', $cafeQuery->count())
                ->description('Ekosistem WadahNgopi')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary')
                ->chart(
                    \App\Models\Cafe::selectRaw('count(*) as count')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->groupByRaw('DATE(created_at)')
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Kafe Perlu Review', Cafe::where('status', 'review')->when(! $isDeveloper, fn ($q) => $q->where('owner_id', $user->id))->count())
                ->description($isDeveloper ? 'Menunggu tinjauan admin' : 'Sedang diverifikasi developer')
                ->descriptionIcon('heroicon-m-magnifying-glass-circle')
                ->color('warning'),

            Stat::make('Total Pengunjung', number_format(\App\Models\Information::sum('views')))
                ->description('Total pembacaan informasi')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('success'),
        ];

        if ($user->role === 'roastery' || $isDeveloper) {
            $roasteryQuery = \App\Models\Roastery::query();
            if (! $isDeveloper) {
                $roasteryQuery->where('owner_id', $user->id);
            }

            $stats[] = Stat::make('Total Roastery', $roasteryQuery->count())
                ->description($isDeveloper ? 'Katalog biji kopi' : 'Roastery kamu yang terdaftar')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info');
        }

        return $stats;
    }
}
