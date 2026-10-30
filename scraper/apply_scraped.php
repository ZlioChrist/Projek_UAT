<?php
// Apply scraped live data (scraper/scrape_all output JSON) to the local database.
// Usage: php scraper/apply_scraped.php <scraped.json>
// Expects the "script" JSON body (results array) — either raw output file or extracted JSON.

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cafe;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

$raw = file_get_contents($argv[1] ?? exit("Usage: php scraper/apply_scraped.php <scraped.json>\n"));

// Extract the JSON object from the browser report (starts at first '{' after "script")
if (! preg_match('/\{[\s\S]*\}/', $raw, $m)) exit("ERR: no JSON found\n");
$json = json_decode($m[0], true);
if (! $json || empty($json['results'])) exit("ERR: bad JSON shape\n");

$results = $json['results'];
$updated = 0;
$notFound = 0;
$imagesDownloaded = 0;
$galleryImagesDownloaded = 0;
$statusesUpdated = 0;

foreach ($results as $r) {
    $cafe = Cafe::where('slug', $r['slug'] ?? '')->first();
    if (! $cafe) {
        $notFound++;
        continue;
    }

    $payload = [];

    // Name / address / est
    if (! empty($r['name']) && $r['name'] !== $cafe->name) $payload['name'] = $r['name'];
    if (! empty($r['address']) && $r['address'] !== $cafe->address) $payload['address'] = $r['address'];

    // The local schema supports weekday/weekend schedules only; import a group
    // only when every day in that group has the same source schedule.
    $hours = $r['hours'] ?? [];
    if (! empty($hours)) {
        $normalizeTime = static function ($value): ?string {
            $value = str_replace('.', ':', trim((string) $value));
            if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches) !== 1) {
                return null;
            }

            return sprintf('%02d:%s', (int) $matches[1], $matches[2]);
        };

        foreach ([
            'weekday' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'weekend' => ['Sabtu', 'Minggu'],
        ] as $group => $days) {
            $schedules = collect($days)->map(function ($day) use ($hours, $normalizeTime) {
                $schedule = $hours[$day] ?? null;
                $open = $normalizeTime($schedule['open'] ?? null);
                $close = $normalizeTime($schedule['close'] ?? null);

                return $open && $close ? ['open' => $open, 'close' => $close] : null;
            });

            $uniqueSchedules = $schedules->filter()->unique(fn ($schedule) => $schedule['open'].'-'.$schedule['close']);
            if ($schedules->every(fn ($schedule) => $schedule !== null) && $uniqueSchedules->count() === 1) {
                $payload['operating_hours'][$group] = $uniqueSchedules->first();
            }
        }
    }

    $status = preg_replace('/\s+/u', ' ', trim((string) ($r['status'] ?? ''))) ?? '';
    if ($status !== '') {
        $isOpen = preg_match('/^(?:SEDANG\s+)?BUKA\b/i', $status) === 1 || stripos($status, '24 jam') !== false;
        $statusText = null;
        if (stripos($status, '24 jam') !== false) {
            $statusText = '24 Jam';
            $payload['is_24_hours'] = true;
        } elseif (preg_match('/(?:Sampai|Buka)\s+\d{1,2}:\d{2}/i', $status, $match) === 1) {
            $statusText = $match[0];
        }

        $payload['official_is_open'] = $isOpen;
        if ($statusText !== null) {
            $payload['official_status_text'] = $statusText;
        }
        $payload['official_status_checked_at'] = now();
        $statusesUpdated++;
    }

    // Socials -> social_links JSON (match local schema: [{platform, url, show}])
    if (! empty($r['socials'])) {
        $links = [];
        foreach ($r['socials'] as $url) {
            $url = html_entity_decode($url);
            $platform = match (true) {
                str_contains($url, 'instagram') => 'instagram',
                str_contains($url, 'tiktok')    => 'tiktok',
                str_contains($url, 'wa.me'), str_contains($url, 'whatsapp') => 'whatsapp',
                str_contains($url, 'facebook')  => 'facebook',
                default => 'other',
            };
            $links[] = ['platform' => $platform, 'url' => $url, 'show' => true];
        }
        if ($links !== []) $payload['social_links'] = $links;
    }

    // Preserve the official Maps link and resolve coordinates when missing.
    if (! empty($r['mapsUrl'])) {
        $payload['google_maps_url'] = $r['mapsUrl'];
    }
    if (! empty($r['mapsUrl']) && ($cafe->latitude === null || $cafe->longitude === null)) {
        try {
            $resp = Http::withoutVerifying()->timeout(15)->get($r['mapsUrl']);
            $final = (string) $resp->effectiveUri();
            if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $final, $mm)) {
                $payload['latitude'] = $mm[1];
                $payload['longitude'] = $mm[2];
            } elseif (preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $final, $mm)) {
                $payload['latitude'] = $mm[1];
                $payload['longitude'] = $mm[2];
            }
        } catch (\Throwable $e) { /* keep null */
        }
    }

    if ($payload !== []) {
        $cafe->update($payload);
        $updated++;
    }

    $sourceImages = collect($r['images'] ?? [])
        ->filter(fn ($url) => is_string($url) && $url !== '' && ! str_contains($url, '/menu-gallery/'))
        ->values();

    // Download the cafe gallery separately from the already-imported menu photos.
    $main = $sourceImages->first(fn ($url) => ! str_contains($url, '/gallery/'));
    $galleryUrls = $sourceImages->reject(fn ($url) => $url === $main)->unique()->values();
    $galleryPaths = [];
    foreach ($galleryUrls as $i => $url) {
        $full = str_starts_with($url, 'http') ? $url : 'https://wadahngopi.com' . $url;
        try {
            $resp = Http::withoutVerifying()->timeout(30)->get($full);
            if ($resp->successful()) {
                $ext = pathinfo(parse_url($full, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $path = 'cafes/gallery/'.$cafe->slug.'-'.($i + 1).'.'.$ext;
                Storage::disk('public')->put($path, $resp->body());
                $galleryPaths[] = $path;
                $galleryImagesDownloaded++;
            }
        } catch (\Throwable $e) { /* skip */
        }
    }
    if ($galleryPaths !== []) {
        $cafe->update(['images' => array_values(array_unique(array_merge($cafe->images ?? [], $galleryPaths)))]);
    }

    // Main image: download the first non-gallery cafe image when no cover exists.
    if (! empty($r['images']) && empty($cafe->image_path)) {
        if ($main) {
            $full = str_starts_with($main, 'http') ? $main : 'https://wadahngopi.com' . $main;
            try {
                $resp = Http::withoutVerifying()->timeout(30)->get($full);
                if ($resp->successful()) {
                    $ext = pathinfo(parse_url($full, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = 'cafes/' . $cafe->slug . '-main.' . $ext;
                    Storage::disk('public')->put($filename, $resp->body());
                    $cafe->update(['image_path' => $filename]);
                    $imagesDownloaded++;
                }
            } catch (\Throwable $e) { /* skip */
            }
        }
    }
}

echo "DONE updated={$updated} statuses={$statusesUpdated} galleryImages={$galleryImagesDownloaded} covers={$imagesDownloaded} notFound={$notFound}\n";
