<?php

namespace App\Observers;

use App\Models\Cafe;
use App\Services\BusinessUpdateNotifier;
use App\Services\PaymentQrUpdateNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CafeObserver
{
    /**
     * Handle the Cafe "created" event.
     */
    public function created(Cafe $cafe): void
    {
        $this->clearCache($cafe);
    }

    /**
     * Handle the Cafe "updated" event.
     */
    public function updated(Cafe $cafe): void
    {
        $this->clearCache($cafe);

        $publicFields = [
            'name', 'description', 'address', 'city_id', 'google_maps_url',
            'whatsapp_number', 'image_path', 'images', 'menu_images',
            'operating_hours', 'weekday_open', 'weekday_close',
            'weekend_open', 'weekend_close', 'payment_qr_path', 'payment_instructions',
            'bank_name', 'bank_account_number', 'bank_account_name',
        ];

        if ($cafe->wasChanged('payment_qr_path')) {
            app(PaymentQrUpdateNotifier::class)->notify($cafe);
        }

        if ($cafe->status === 'published' && $cafe->wasChanged($publicFields)) {
            app(BusinessUpdateNotifier::class)->notify(
                $cafe,
                'Informasi kafe diperbarui',
                "Informasi {$cafe->name} yang Anda simpan baru saja diperbarui.",
            );
        }
    }

    /**
     * Handle the Cafe "deleted" event.
     */
    public function deleted(Cafe $cafe): void
    {
        $this->clearCache($cafe);
    }

    /**
     * Handle the Cafe "restored" event.
     */
    public function restored(Cafe $cafe): void
    {
        $this->clearCache($cafe);
    }

    /**
     * Handle the Cafe "force deleted" event.
     */
    public function forceDeleted(Cafe $cafe): void
    {
        $this->clearCache($cafe);
    }

    /**
     * Clear relevant caches.
     */
    private function clearCache(?Cafe $cafe = null): void
    {
        Cache::forget('home_cafes');
        Cache::forget('active_cafe_ids');

        // Clear random order cache (legacy 10 buckets)
        for ($i = 0; $i < 10; $i++) {
            Cache::forget("cafe_random_order_{$i}");
        }

        // Clear all shuffled and total count caches via Redis SCAN (non-blocking)
        // Only if using Redis driver to avoid exceptions on shared hosting (File cache)
        if (config('cache.default') === 'redis' || config('cache.stores.redis.driver') === 'redis') {
            try {
                $prefix = config('cache.prefix', 'laravel_cache').':';

                foreach (['shuffled_v7_*', 'shuffled_v8_*', 'total_v7_*', 'total_v8_*'] as $pattern) {
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
                // Fallback: If Redis pattern clear fails, the cache will expire naturally (5-30 min)
            }
        }

        if ($cafe) {
            // Must match CafeController's cache key format: "cafe_{slug}"
            Cache::forget("cafe_{$cafe->slug}");
        }
    }
}
