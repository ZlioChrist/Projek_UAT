<?php

use App\Services\CafeVoiceSearchService;

it('parses Indonesian price formats and keeps the configured fallback', function () {
    $service = new CafeVoiceSearchService;

    expect($service->budgetFromCommand('Cari Wi-Fi harga maksimal Rp5.000', 5000))->toBe(5000)
        ->and($service->budgetFromCommand('Wi-Fi, harga di bawah 10 ribu', 5000))->toBe(10000)
        ->and($service->budgetFromCommand('Wi-Fi dan mushola', 5000))->toBe(5000);
});
