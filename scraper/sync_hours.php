<?php
// Sync operating hours for local cafes from live wadahngopi.com cafe cards.
// The live explore card badge shows "BUKA Sampai HH:MM" / "TUTUP Buka HH:MM".
// We derive today's open/close from that, then persist per weekday/weekend slots.

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cafe;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

$caBundle = config('services.wadahngopi.ca_bundle');

$response = Http::retry(2, 500)
    ->timeout(30)
    ->withOptions(['verify' => $caBundle ?: true])
    ->withUserAgent('WadahNgopi local hours sync')
    ->get('https://wadahngopi.com/explore');

if (! $response->successful()) {
    echo 'ERR: HTTP ' . $response->status() . PHP_EOL;
    exit(1);
}

$html = $response->body();
$cards = (new Crawler($html))->filter('main.explore-cafe-grid a.cafe-card-2026');
if ($cards->count() === 0) {
    echo "ERR: no cafe cards found\n";
    exit(1);
}

$updated = 0;
$skipped = 0;

$cards->each(function (Crawler $card) use (&$updated, &$skipped): void {
    $path = parse_url((string) $card->attr('href'), PHP_URL_PATH);
    if (! is_string($path) || preg_match('#^/cafes/([a-z0-9-]+)/?$#i', $path, $m) !== 1) return;
    $slug = $m[1];

    $cafe = Cafe::where('slug', $slug)->first();
    if (! $cafe) {
        $skipped++;
        return;
    }

    $badge = $card->filter('.cafe-status-badge-smart');
    if ($badge->count() !== 1) {
        $skipped++;
        return;
    }

    $text = trim(preg_replace('/\s+/u', ' ', $badge->text()) ?? '');
    $isOpen = in_array('open', preg_split('/\s+/', trim((string) $badge->attr('class'))) ?: [], true);

    // Parse badge: "BUKA Sampai 00:00" / "TUTUP Buka 17:00" / "24 Jam"
    $close = null;
    $open = null;
    if (preg_match('/Sampai\s+(\d{1,2}:\d{2})/u', $text, $mm)) {
        $close = $mm[1];
    } elseif (preg_match('/Buka\s+(\d{1,2}:\d{2})/u', $text, $mm)) {
        $open = $mm[1];
    }

    // Fill the missing side from the current time (approximate, refined each sync run)
    $now = now()->format('H:i');
    if ($isOpen && $close !== null) {
        $open = $open ?? (strtotime($close) < strtotime($now) ? '00:00' : $now);
    } elseif (! $isOpen && $open !== null) {
        $close = $close ?? $open;
        $open = $now;
    }

    if ($open === null && $close === null) {
        $skipped++;
        return;
    }

    $isWeekend = in_array(now()->dayOfWeek, [0, 6]);
    $prefix = $isWeekend ? 'weekend' : 'weekday';

    // Only fill empty columns; keep any existing richer schedule
    $payload = [];
    if ($cafe->{$prefix . '_open'} === null && $open !== null) $payload[$prefix . '_open'] = $open;
    if ($cafe->{$prefix . '_close'} === null && $close !== null) $payload[$prefix . '_close'] = $close;

    if ($payload !== []) {
        $cafe->update($payload);
        $updated++;
        echo "{$slug}: {$prefix} " . json_encode($payload) . "\n";
    } else {
        $skipped++;
    }
});

echo "DONE updated={$updated} skipped={$skipped}\n";
