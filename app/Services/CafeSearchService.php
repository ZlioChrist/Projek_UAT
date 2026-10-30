<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CafeSearchService
{
    public function isWeekend(): bool
    {
        return in_array(now()->dayOfWeek, [0, 6]);
    }

    /**
     * Scope query to find cafes that are currently open.
     */
    public function scopeOpenNow(Builder $query): Builder
    {
        $now = now()->format('H:i:s');
        $dailyNow = now()->format('H:i');
        $isWeekend = $this->isWeekend();
        $modelTable = $query->getModel()->getTable();
        $day = match (now()->dayOfWeekIso) {
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            default => 'sunday',
        };
        $yesterday = match (now()->subDay()->dayOfWeekIso) {
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            default => 'sunday',
        };

        $prefix = $isWeekend ? 'weekend' : 'weekday';
        $openCol = "{$prefix}_open";
        $closeCol = "{$prefix}_close";
        $dailyOpen = "operating_hours->{$day}->open";
        $dailyClose = "operating_hours->{$day}->close";
        $yesterdayOpen = "operating_hours->{$yesterday}->open";
        $yesterdayClose = "operating_hours->{$yesterday}->close";
        $dailyDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        $scheduledHours = function (Builder $q) use ($now, $dailyNow, $openCol, $closeCol, $dailyOpen, $dailyClose, $day, $yesterday, $yesterdayOpen, $yesterdayClose, $dailyDays): void {
            $q->where('is_24_hours', true)
                ->orWhere(function (Builder $daily) use ($dailyNow, $dailyOpen, $dailyClose, $day): void {
                    $daily->whereJsonContainsKey("operating_hours->{$day}")
                        ->whereNotNull($dailyOpen)
                        ->whereNotNull($dailyClose);
                    $this->whereCurrentDayRange($daily, $dailyOpen, $dailyClose, $dailyNow);
                })
                ->orWhere(function (Builder $overnight) use ($dailyNow, $yesterday, $yesterdayOpen, $yesterdayClose): void {
                    $overnight->whereJsonContainsKey("operating_hours->{$yesterday}")
                        ->whereNotNull($yesterdayOpen)
                        ->whereNotNull($yesterdayClose)
                        ->whereColumn($yesterdayClose, '<', $yesterdayOpen)
                        ->where($yesterdayClose, '>=', $dailyNow);
                })
                ->orWhere(function (Builder $legacy) use ($now, $openCol, $closeCol, $dailyDays): void {
                    $legacy->where(function (Builder $noDailyHours) use ($dailyDays): void {
                        foreach ($dailyDays as $dailyDay) {
                            $noDailyHours->whereJsonDoesntContainKey("operating_hours->{$dailyDay}");
                        }
                    })
                        ->whereNotNull($openCol)
                        ->whereNotNull($closeCol);
                    $this->whereTimeRange($legacy, $openCol, $closeCol, $now);
                });
        };

        if (! in_array($modelTable, ['cafes', 'roasteries'], true)) {
            return $query->where($scheduledHours);
        }

        $staleBefore = now()->subMinutes(15);

        return $query->where(function (Builder $availability) use ($scheduledHours, $staleBefore): void {
            $availability
                ->where(function (Builder $official): void {
                    $official->where('official_is_open', true)
                        ->whereNotNull('official_status_checked_at')
                        ->where('official_status_checked_at', '>=', now()->subMinutes(15))
                        ->where('official_status_checked_at', '<=', now());
                })
                ->orWhere(function (Builder $fallback) use ($scheduledHours, $staleBefore): void {
                    $fallback
                        ->where(function (Builder $stale) use ($staleBefore): void {
                            $stale->whereNull('official_status_checked_at')
                                ->orWhere('official_status_checked_at', '<', $staleBefore);
                        })
                        ->where($scheduledHours);
                });
        });
    }

    private function whereTimeRange(Builder $query, string $open, string $close, string $now): void
    {
        $query->where(function (Builder $time) use ($open, $close, $now): void {
            $time->where(function (Builder $normal) use ($open, $close, $now): void {
                $normal->whereColumn($close, '>', $open)
                    ->where($open, '<=', $now)
                    ->where($close, '>=', $now);
            })->orWhere(function (Builder $overnight) use ($open, $close, $now): void {
                $overnight->whereColumn($close, '<', $open)
                    ->where(function (Builder $current) use ($open, $close, $now): void {
                        $current->where($open, '<=', $now)
                            ->orWhere($close, '>=', $now);
                    });
            });
        });
    }

    private function whereCurrentDayRange(Builder $query, string $open, string $close, string $now): void
    {
        $query->where(function (Builder $time) use ($open, $close, $now): void {
            $time->where(function (Builder $normal) use ($open, $close, $now): void {
                $normal->whereColumn($close, '>', $open)
                    ->where($open, '<=', $now)
                    ->where($close, '>=', $now);
            })->orWhere(function (Builder $overnightStart) use ($open, $close, $now): void {
                $overnightStart->whereColumn($close, '<', $open)
                    ->where($open, '<=', $now);
            });
        });
    }

    /**
     * Scope query to order by nearest location using a database-portable distance formula.
     */
    public function scopeNearest(Builder $query, float $lat, float $lng, int $radiusKm = 20): Builder
    {
        $latitudeDelta = $radiusKm / 111.045;
        $longitudeScale = cos(deg2rad($lat));
        $longitudeDelta = abs($longitudeScale) < 0.000001
            ? 180
            : min(180, $radiusKm / (111.045 * abs($longitudeScale)));
        $minLongitude = $lng - $longitudeDelta;
        $maxLongitude = $lng + $longitudeDelta;

        $haversine = '(
            6371 * ACOS(
                LEAST(1, GREATEST(-1, COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                COS(RADIANS(longitude) - RADIANS(?)) +
                SIN(RADIANS(?)) * SIN(RADIANS(latitude))))
            )
        )';

        if ($query->getQuery()->columns === null) {
            $query->select($query->getModel()->qualifyColumn('*'));
        }

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [
                max(-90, $lat - $latitudeDelta),
                min(90, $lat + $latitudeDelta),
            ])
            ->where(function (Builder $longitude) use ($minLongitude, $maxLongitude): void {
                if ($minLongitude < -180) {
                    $longitude->whereBetween('longitude', [-180, $maxLongitude])
                        ->orWhereBetween('longitude', [$minLongitude + 360, 180]);

                    return;
                }

                if ($maxLongitude > 180) {
                    $longitude->whereBetween('longitude', [$minLongitude, 180])
                        ->orWhereBetween('longitude', [-180, $maxLongitude - 360]);

                    return;
                }

                $longitude->whereBetween('longitude', [$minLongitude, $maxLongitude]);
            })
            ->whereRaw("{$haversine} <= ?", [$lat, $lng, $lat, $radiusKm])
            ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
            ->orderBy('distance');
    }

    /**
     * Scope query to search using Fulltext Index with LIKE fallback.
     * Short terms (≤3 chars) use LIKE since fulltext min word length is typically 3-4.
     * If FULLTEXT index is missing (e.g. on hosting where migration didn't run),
     * gracefully falls back to LIKE-only search instead of crashing.
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        // Sanitize wildcards to prevent LIKE injection (% and _ are SQL wildcards)
        $safeTerm = str_replace(['%', '_'], ['\%', '\_'], $term);

        // For short terms, Fulltext might not trigger well, use LIKE
        if (strlen($term) <= 3) {
            $query->where(function ($q) use ($safeTerm) {
                $q->where('name', 'like', "%{$safeTerm}%")
                    ->orWhere('address', 'like', "%{$safeTerm}%");
            });

            return;
        }

        // Sanitize term to prevent SQL syntax errors from boolean operators
        $sanitizedTerm = preg_replace('/[+\-><()~*\"@]/', ' ', $term);
        $sanitizedTerm = trim(preg_replace('/\s+/', ' ', $sanitizedTerm));

        // Try Fulltext first, fallback to LIKE if index doesn't exist
        // Cache the FULLTEXT index check per-table for 1 hour to avoid extra query per search
        $tableName = $query->getModel()->getTable();
        $hasFulltext = Cache::remember(
            "fulltext_index_{$tableName}",
            now()->addHour(),
            function () use ($tableName) {
                try {
                    DB::select(
                        "SELECT 1 FROM `{$tableName}` WHERE MATCH(`name`, `address`, `description`) AGAINST('test' IN BOOLEAN MODE) LIMIT 1"
                    );

                    return true;
                } catch (QueryException $e) {
                    return false;
                }
            }
        );

        if ($hasFulltext) {
            $query->where(function ($q) use ($safeTerm, $sanitizedTerm) {
                $q->whereFullText(['name', 'address', 'description'], $sanitizedTerm, ['mode' => 'boolean'])
                    ->orWhere('name', 'like', "%{$safeTerm}%");
            });
        } else {
            $query->where(function ($q) use ($safeTerm) {
                $q->where('name', 'like', "%{$safeTerm}%")
                    ->orWhere('address', 'like', "%{$safeTerm}%")
                    ->orWhere('description', 'like', "%{$safeTerm}%");
            });
        }
    }

    /**
     * Check if a specific time is within range.
     * Handles overnight hours.
     */
    public function isTimeInRange(string $open, string $close, ?string $currentTime = null): bool
    {
        $now = $currentTime ?: now()->format('H:i:s');

        // Normalize to H:i:s format
        $open = strlen($open) === 5 ? $open.':00' : $open;
        $close = strlen($close) === 5 ? $close.':00' : $close;

        // If open and close are exactly the same, treat as 24-hours or misconfig
        if ($open === $close) {
            return true;
        }

        if ($close < $open) {
            // Cross-day logic: Open 22:00, Close 02:00
            // Current time 01:00 is VALID (<= 02:00)
            // Current time 23:00 is VALID (>= 22:00)
            return ($now >= $open && $now <= '23:59:59') || ($now >= '00:00:00' && $now <= $close);
        }

        // Standard case (Open 08:00, Close 22:00)
        // Match MySQL BETWEEN behavior (inclusive)
        return $now >= $open && $now <= $close;

    }
}
