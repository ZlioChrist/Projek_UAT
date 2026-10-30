<?php
// Scrape live wadahngopi.com cafe detail pages: parse cafeDetailComponent(...) payload
$slugs = json_decode(file_get_contents($argv[1] ?? 'php://stdin'), true);

$base = 'https://wadahngopi.com/cafes/';
$ctx = stream_context_create(['http' => ['timeout' => 25, 'header' => "User-Agent: Mozilla/5.0\r\n"]]);

$out = [];
foreach ($slugs as $slug) {
    $html = @file_get_contents($base . $slug, false, $ctx);
    if (! $html) {
        $out[$slug] = ['error' => 'fetch failed'];
        continue;
    }
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (! preg_match('/cafeDetailComponent\((\{.*?\})\)/s', $html, $m)) {
        // fallback: looser match
        if (! preg_match('/cafeDetailComponent\((.*?)\)\s*>/s', $html, $m)) {
            $out[$slug] = ['error' => 'no component payload'];
            continue;
        }
    }
    $payload = $m[1];
    // The payload contains newlines/indentation inside; it's JS object literal.
    // Convert JS to JSON: quote unquoted keys
    $payload = preg_replace("/([{,]\s*)([A-Za-z_][A-Za-z0-9_]*)\s*:/", '$1"$2":', $payload);
    $payload = str_replace(['\/', "\n", "\r"], ['/', ' ', ' '], $payload);
    $data = json_decode($payload, true);

    // Extract status badge text from the page
    $status = '';
    if (preg_match('/official_status_text[^>]*>([^<]+)</', $html, $sm)) $status = trim($sm[1]);
    elseif (preg_match('/(BUKA|TUTUP)[^<]{0,20}/', strip_tags($html), $sm)) $status = trim($sm[0]);

    // Extract operating hours text: Senin ... Minggu block from plain text
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    $hours = '';
    if (preg_match('/Senin.{0,800}?Minggu/u', $text, $hm)) {
        $hours = trim($hm[0]);
    }

    $out[$slug] = [
        'payload' => $data,
        'status'  => $status,
        'hours'   => $hours,
    ];
}

header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
