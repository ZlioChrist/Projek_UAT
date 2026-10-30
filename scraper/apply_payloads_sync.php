<?php
// Apply payloads synchronously: local cafe IDs already match live payload IDs, so update menu_images directly + download images.
// Usage: php scraper/apply_payloads_sync.php <payloads.json>

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cafe;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

$raw = file_get_contents($argv[1] ?? exit("Usage: php scraper/apply_payloads_sync.php <payloads.json>\n"));
if (! preg_match('/\{[\s\S]*\}/', $raw, $m)) exit("ERR: no JSON\n");
$json = json_decode($m[0], true);
$results = $json['results'] ?? exit("ERR: no results\n");

$stored = 0;
$rows = 0;
$notFound = 0;
$cafeIds = Cafe::pluck('id', 'id')->all();

foreach ($results as $r) {
    $payload = $r['payload'] ?? null;
    $id = $payload['id'] ?? null;
    if (! $id || ! isset($cafeIds[$id])) {
        $notFound++;
        continue;
    }
    $cafe = Cafe::find($id);

    $menuImages = $payload['menuImages'] ?? [];
    if ($menuImages === []) continue;

    // 1. Download each menu image (Daftar Menu photos) to local storage
    $paths = [];
    foreach ($menuImages as $i => $mi) {
        $url = $mi['url'] ?? null;
        if (! $url) continue;
        $full = str_starts_with($url, 'http') ? $url : 'https://wadahngopi.com' . $url;
        $ext = pathinfo(parse_url($full, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $path = "cafes/menu/{$cafe->slug}-menu-" . ($i + 1) . ".{$ext}";
        try {
            $resp = Http::withoutVerifying()->timeout(30)->get($full);
            if ($resp->successful()) {
                Storage::disk('public')->put($path, $resp->body());
                $paths[] = $path;
                $stored++;
            }
        } catch (\Throwable $e) { /* skip image */
        }
    }

    // 2. Update menu_images column + optional menu image model row
    if ($paths !== []) {
        $cafe->update(['menu_images' => $paths]);
        $rows++;
    }
}

echo "DONE images={$stored} cafesUpdated={$rows} notFound={$notFound}\n";
