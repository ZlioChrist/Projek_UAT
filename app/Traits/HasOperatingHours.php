<?php

namespace App\Traits;

use App\Services\CafeSearchService;
use Illuminate\Support\Str;

/**
 * Shared trait for models with operating hours logic.
 * Used by Cafe and Roastery models to avoid code duplication.
 */
trait HasOperatingHours
{
    /**
     * Check if the entity is currently open.
     * Supports: 24-hour, weekday/weekend schedules, legacy single time fields.
     */
    public function getIsOpenAttribute(): bool
    {
        if ($this->officialStatusIsFresh()) {
            return (bool) $this->getAttribute('official_is_open');
        }

        if ($this->is_24_hours) {
            return true;
        }

        $todayHours = $this->today_hours;
        if ($todayHours && ! empty($todayHours['open']) && ! empty($todayHours['close'])) {
            if ($todayHours['close'] < $todayHours['open']) {
                if (now()->format('H:i:s') >= $this->timeWithSeconds($todayHours['open'])) {
                    return true;
                }
            } elseif ($this->checkTimeInRange($todayHours['open'], $todayHours['close'])) {
                return true;
            }
        }

        $yesterdayHours = $this->hoursForDay(now()->subDay()->dayOfWeekIso);
        if ($yesterdayHours !== null
            && $yesterdayHours['close'] < $yesterdayHours['open']
            && now()->format('H:i:s') <= $this->timeWithSeconds($yesterdayHours['close'])) {
            return true;
        }

        // Legacy fallback for Cafe model
        if (property_exists($this, 'opening_time') || isset($this->attributes['opening_time'])) {
            if (! $this->opening_time || ! $this->closing_time) {
                return false;
            }

            return $this->checkTimeInRange($this->opening_time, $this->closing_time);
        }

        return false;
    }

    public function getOfficialStatusIsFreshAttribute(): bool
    {
        return $this->officialStatusIsFresh();
    }

    /**
     * Get today's operating hours for display.
     *
     * @return array{open: string, close: string, label?: string}|null
     */
    public function getTodayHoursAttribute(): ?array
    {
        if ($this->is_24_hours) {
            return ['open' => '00:00', 'close' => '24:00', 'label' => '24 Jam'];
        }

        $day = match (now()->dayOfWeekIso) {
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            default => 'sunday',
        };
        $hours = $this->operating_hours ?? [];

        if (array_key_exists($day, $hours)) {
            $schedule = $hours[$day];

            if ($schedule && ! empty($schedule['open']) && ! empty($schedule['close'])) {
                return $schedule;
            }

            return null;
        }

        if ($this->hasDailySchedule($hours)) {
            return null;
        }

        $prefix = in_array($day, ['saturday', 'sunday'], true) ? 'weekend' : 'weekday';
        $open = $this->{$prefix.'_open'};
        $close = $this->{$prefix.'_close'};

        if ($open && $close) {
            return ['open' => $open, 'close' => $close];
        }

        $schedule = $hours[$prefix] ?? null;
        if ($schedule && ! empty($schedule['open']) && ! empty($schedule['close'])) {
            return $schedule;
        }

        // Legacy fallback
        if (isset($this->opening_time) && isset($this->closing_time)) {
            return ['open' => $this->opening_time, 'close' => $this->closing_time];
        }

        return null;
    }

    /**
     * @return array{open: string, close: string}|null
     */
    private function hoursForDay(int $isoDay): ?array
    {
        $day = match ($isoDay) {
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            default => 'sunday',
        };
        $hours = $this->operating_hours ?? [];

        if (array_key_exists($day, $hours)) {
            $schedule = $hours[$day];

            return $schedule && ! empty($schedule['open']) && ! empty($schedule['close'])
                ? $schedule
                : null;
        }

        if ($this->hasDailySchedule($hours)) {
            return null;
        }

        $group = in_array($day, ['saturday', 'sunday'], true) ? 'weekend' : 'weekday';
        $open = $this->{$group.'_open'} ?: data_get($hours, "{$group}.open");
        $close = $this->{$group.'_close'} ?: data_get($hours, "{$group}.close");

        if (! $open && ! $close && isset($this->opening_time, $this->closing_time)) {
            $open = $this->opening_time;
            $close = $this->closing_time;
        }

        return $open && $close ? ['open' => $open, 'close' => $close] : null;
    }

    private function hasDailySchedule(array $hours): bool
    {
        return array_intersect(
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            array_keys($hours),
        ) !== [];
    }

    private function timeWithSeconds(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function officialStatusIsFresh(): bool
    {
        $attributes = $this->getAttributes();
        if (! array_key_exists('official_is_open', $attributes)
            || ! array_key_exists('official_status_checked_at', $attributes)) {
            return false;
        }

        $checkedAt = $this->getAttribute('official_status_checked_at');

        return $checkedAt !== null
            && $checkedAt->greaterThanOrEqualTo(now()->subMinutes(15))
            && $checkedAt->lessThanOrEqualTo(now());
    }

    /**
     * Check if current time is within given range.
     */
    private function checkTimeInRange(string $open, string $close): bool
    {
        return app(CafeSearchService::class)->isTimeInRange($open, $close);
    }

    /**
     * Generate a unique slug for the model.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        // withoutGlobalScopes() prevents SoftDeletes scope from adding
        // WHERE deleted_at IS NULL (which crashes during migrate:fresh)
        while (static::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }
}
