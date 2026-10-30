<?php

namespace App\Console\Commands;

use App\Models\Cafe;
use App\Models\Roastery;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SyncOfficialPlaceCoordinates extends Command
{
    protected $signature = 'app:sync-official-place-coordinates';

    protected $description = 'Fill missing cafe and roastery coordinates from their official Google Maps links';

    public function handle(): int
    {
        $updated = 0;
        $failed = 0;

        foreach ([Cafe::class => 'cafe', Roastery::class => 'roastery'] as $model => $label) {
            $places = $model::query()
                ->where('status', 'published')
                ->whereNotNull('google_maps_url')
                ->where('google_maps_url', '!=', '')
                ->where(function ($query): void {
                    $query->whereNull('latitude')->orWhereNull('longitude');
                })
                ->get(['id', 'slug', 'google_maps_url', 'latitude', 'longitude']);

            foreach ($places as $place) {
                if ($place->latitude !== null || $place->longitude !== null) {
                    $this->warn("Skipped {$label} {$place->slug}: coordinate pair is incomplete.");
                    $failed++;

                    continue;
                }

                try {
                    $coordinates = $this->resolveCoordinates($place->google_maps_url);
                    if ($coordinates === null) {
                        $this->warn("No valid coordinates found for {$label} {$place->slug}.");
                        $failed++;

                        continue;
                    }

                    $model::query()
                        ->whereKey($place->getKey())
                        ->whereNull('latitude')
                        ->whereNull('longitude')
                        ->update([
                            'latitude' => $coordinates['latitude'],
                            'longitude' => $coordinates['longitude'],
                        ]);
                    Cache::forget("{$label}_{$place->slug}");
                    $updated++;
                } catch (ConnectionException|\RuntimeException $exception) {
                    $this->warn("Could not resolve {$label} {$place->slug}: {$exception->getMessage()}");
                    $failed++;
                }

                usleep(150_000);
            }
        }

        $this->info("Updated coordinates for {$updated} published places.");
        if ($failed > 0) {
            $this->warn("Could not resolve coordinates for {$failed} places; see warnings above.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function resolveCoordinates(string $url): ?array
    {
        if (! $this->isGoogleMapsUrl($url)) {
            throw new \RuntimeException('The saved map link is not a trusted Google Maps URL.');
        }

        $response = Http::connectTimeout(5)
            ->timeout(12)
            ->withUserAgent('WadahNgopi location sync')
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("Google Maps returned HTTP {$response->status()}.");
        }

        $effectiveUrl = $response->effectiveUri()?->__toString()
            ?? $response->header('Location')
            ?? $url;

        if (! $this->isGoogleMapsUrl($effectiveUrl)) {
            throw new \RuntimeException('The map link redirected outside Google Maps.');
        }

        return $this->coordinatesFromUrl($effectiveUrl);
    }

    private function isGoogleMapsUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');

        return ($parts['scheme'] ?? '') === 'https'
            && ($host === 'maps.app.goo.gl'
                || $host === 'goo.gl'
                || $host === 'google.com'
                || str_ends_with($host, '.google.com'));
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function coordinatesFromUrl(string $url): ?array
    {
        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $url, $matches) === 1
            || preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $url, $matches) === 1) {
            return $this->validatedCoordinates((float) $matches[1], (float) $matches[2]);
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        foreach (['q', 'query', 'll'] as $parameter) {
            $value = $query[$parameter] ?? null;
            if (is_string($value)
                && preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $value, $matches) === 1) {
                return $this->validatedCoordinates((float) $matches[1], (float) $matches[2]);
            }
        }

        return null;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function validatedCoordinates(float $latitude, float $longitude): ?array
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }
}
