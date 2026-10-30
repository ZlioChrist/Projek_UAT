<?php
// Apply payload data (live IDs, gallery & menu images) to local cafes + download menu images.
// Usage: php scraper/apply_payloads.php <payloads.json>

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cafe;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

$raw = file_get_contents($argv[1] ?? exit("Usage: php scraper/apply_payloads.php <payloads.json>\n"));
// Handle UTF-16 (PowerShell redirect) encoding
if (substr($raw, 0, 2) === "\xFF\xFE" || substr($raw, 0, 2) === "\xFE\xFF" || (str_contains(substr($raw, 0, 200), "\x00") && ! str_contains(substr($raw, 0, 2), "\xEF"))) {
    $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
}
if (! preg_match('/\{[\s\S]*\}/', $raw, $m)) exit("ERR: no JSON\n");
$json = json_decode($m[0], true);
$results = $json['results'] ?? exit("ERR: no results\n");

$menuImagesStored = 0;
$galleryUpdated = 0;
$notFound = 0;

foreach ($results as $r) {
    $cafe = Cafe::where('slug', $r['slug'] ?? '')->first();
    if (! $cafe) {
        $notFound++;
        continue;
    }
    $payload = $r['payload'] ?? [];

    // 1. Store menu images (Daftar Menu photos) as media linked to this cafe
    $menuImages = $payload['menuImages'] ?? [];
    if ($menuImages !== []) {
        $paths = [];
        foreach ($menuImages as $i => $mi) {
            $url = $mi['url'] ?? null;
            if (! $url) continue;
            $full = str_starts_with($url, 'http') ? $url : 'https://wadahngopi.com' . $url;
            try {
                $resp = Http::withoutVerifying()->timeout(30)->get($full);
                if ($resp->successful()) {
                    $ext = pathinfo(parse_url($full, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $path = "cafes/menu/{$cafe->slug}-menu-" . ($i + 1) . ".{$ext}";
                    Storage::disk('public')->put($path, $resp->body());
                    $paths[] = $path;
                    $menuImagesStored++;
                }
            } catch (\Throwable $e) { /* skip image */
            }
        }
        if ($paths !== []) {
            // Store as JSON on existing column if available, else merge into gallery
            if (Schema::hasColumn('cafes', 'menu_images')) {
                $cafe->update(['menu_images' => $paths]);
            }
        }
    }

    // 2. Gallery images live under processed_images (already handled); only update menu payload
    $galleryUpdated = 0;
}

echo "DONE menuImages={$menuImagesStored} galleryUpdated={$galleryUpdated} notFound={$notFound}\n";
