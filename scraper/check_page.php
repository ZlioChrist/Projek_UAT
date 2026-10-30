<?php
// Dump the x-data payload of the cafe detail page
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$slug = $argv[1] ?? 'pot-o-koffie';
$resp = Http::timeout(30)->get("http://127.0.0.1:8000/cafes/{$slug}");
$html = $resp->body();

$i = strpos($html, 'menuImages:');
echo $i === false ? "menuImages not found\n" : substr($html, $i, 500) . "\n";
