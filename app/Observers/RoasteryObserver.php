<?php

namespace App\Observers;

use App\Models\Roastery;
use App\Services\BusinessUpdateNotifier;
use App\Services\PaymentQrUpdateNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class RoasteryObserver
{
    public function created(Roastery $roastery): void
    {
        $this->clearCache($roastery);
    }

    public function updated(Roastery $roastery): void
    {
        $this->clearCache($roastery);

        $publicFields = [
            'name', 'description', 'address', 'city_id', 'google_maps_url',
            'whatsapp_number', 'image_path', 'images', 'menu_images',
            'operating_hours', 'weekday_open', 'weekday_close',
            'weekend_open', 'weekend_close', 'payment_qr_path', 'payment_instructions',
            'bank_name', 'bank_account_number', 'bank_account_name',
        ];

        if ($roastery->wasChanged('payment_qr_path')) {
            app(PaymentQrUpdateNotifier::class)->notify($roastery);
        }

        if ($roastery->status === 'published' && $roastery->wasChanged($publicFields)) {
            app(BusinessUpdateNotifier::class)->notify(
                $roastery,
                'Informasi roastery diperbarui',
                "Informasi {$roastery->name} yang Anda simpan baru saja diperbarui.",
            );
        }
    }

    public function deleted(Roastery $roastery): void
    {
        $this->clearCache($roastery);
    }

    public function restored(Roastery $roastery): void
    {
        $this->clearCache($roastery);
    }

    public function forceDeleted(Roastery $roastery): void
    {
        $this->clearCache($roastery);
    }

    /**
     * Clear relevant caches when roastery data changes.
     */
    private function clearCache(?Roastery $roastery = null): void
    {
        Cache::forget('cities_list');
        Cache::forget('active_roastery_ids');

        // Clear shuffled and total count caches via Redis SCAN (non-blocking)
        // SCAN is O(1) per call vs KEYS which is O(N) and blocks Redis entirely
        try {
            $prefix = config('cache.prefix', 'laravel_cache').':';

            foreach (['shuffled_roastery_*', 'roastery_total_*'] as $pattern) {
                $cursor = null;
                do {
                    [$cursor, $keys] = Redis::scan(
                        $cursor ?? 0,
                        ['match' => $prefix.$pattern, 'count' => 100]
                    );
                    if (! empty($keys)) {
                        Redis::del(...$keys);
                    }
                } while ($cursor);
            }
        } catch (\Throwable $e) {
            // Fallback: cache expires naturally (5-30 min)
        }

        if ($roastery) {
            Cache::forget("roastery_{$roastery->slug}");
        }
    }
}
