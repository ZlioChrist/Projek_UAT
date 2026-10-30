<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cafe;

$total = Cafe::count();
$with = Cafe::whereNotNull('menu_images')->where('menu_images', '!=', '[]')->count();
echo "cafes with menu_images: {$with} / {$total}\n";
Cafe::whereNotNull('menu_images')->where('menu_images', '!=', '[]')->take(3)->get(['id', 'name', 'menu_images'])
    ->each(function ($c) {
        echo $c->id . ' ' . $c->name . ' ' . json_encode($c->menu_images) . "\n";
    });
