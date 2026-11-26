<?php

use App\Livewire\CafeRoulette;
use App\Models\Cafe;
use App\Models\Facility;
use App\Models\MenuItem;
use App\Services\CafeVoiceSearchService;
use App\Services\GoogleMapsGeocodingService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('detects requested facilities without treating negated facilities as requirements', function () {
    $cafe = Cafe::factory()->create();
    Facility::query()->create(['cafe_id' => $cafe->id, 'name' => 'Mushola']);

    $features = app(CafeVoiceSearchService::class)
        ->requestedFeatures('Cari kafe dengan Wi-Fi dan mushola, tidak perlu parkir');

    expect(collect($features)->pluck('key')->all())->toBe(['wifi', 'mushola']);
});

it('geocodes an Indonesian address and reports addresses Google cannot find', function () {
    config(['services.google.maps_geocoding_key' => 'test-geocoding-key']);
    Http::fake([
        'maps.googleapis.com/*' => Http::sequence()
            ->push([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Jalan Juanda, Samarinda, Kalimantan Timur, Indonesia',
                    'geometry' => ['location' => ['lat' => -0.47, 'lng' => 117.15]],
                ]],
            ])
            ->push(['status' => 'ZERO_RESULTS', 'results' => []]),
    ]);

    $service = app(GoogleMapsGeocodingService::class);
    $location = $service->geocode('Jalan Juanda, Samarinda');

    expect($location)->toBe([
        'latitude' => -0.47,
        'longitude' => 117.15,
        'formatted_address' => 'Jalan Juanda, Samarinda, Kalimantan Timur, Indonesia',
    ]);
    expect(fn () => $service->geocode('alamat yang tidak ditemukan'))
        ->toThrow(RuntimeException::class, 'Alamat tidak ditemukan');
});

it('reports when Google Maps geocoding is not configured', function () {
    config(['services.google.maps_geocoding_key' => '']);
    Http::fake();

    expect(fn () => app(GoogleMapsGeocodingService::class)->geocode('Jalan Juanda'))
        ->toThrow(RuntimeException::class, 'GOOGLE_MAPS_GEOCODING_API_KEY');

    Http::assertNothingSent();
});

it('groups nearby cafes by recorded facilities and filters menu prices', function () {
    $complete = Cafe::factory()->create([
        'name' => 'Cafe Lengkap',
        'latitude' => 0.01,
        'longitude' => 0,
        'has_wifi' => true,
    ]);
    $partial = Cafe::factory()->create([
        'name' => 'Cafe Sebagian',
        'latitude' => 0.02,
        'longitude' => 0,
        'has_wifi' => true,
    ]);
    $unrecorded = Cafe::factory()->create([
        'name' => 'Cafe Belum Tercatat',
        'latitude' => 0.005,
        'longitude' => 0,
        'has_wifi' => false,
    ]);
    $overBudget = Cafe::factory()->create([
        'name' => 'Cafe Di Atas Anggaran',
        'latitude' => 0.001,
        'longitude' => 0,
        'has_wifi' => true,
    ]);

    Facility::query()->create(['cafe_id' => $complete->id, 'name' => 'Mushola']);
    Facility::query()->create(['cafe_id' => $partial->id, 'name' => 'Parkir']);

    foreach ([$complete, $partial, $unrecorded] as $cafe) {
        MenuItem::query()->create([
            'cafe_id' => $cafe->id,
            'name' => 'Kopi',
            'price' => 4500,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    MenuItem::query()->create([
        'cafe_id' => $overBudget->id,
        'name' => 'Kopi',
        'price' => 6000,
        'stock' => 10,
        'is_active' => true,
    ]);

    $component = Livewire::test(CafeRoulette::class)
        ->set('voiceCommand', 'Cari kafe dengan Wi-Fi dan mushola harga maksimal Rp5.000')
        ->set('voiceLatitude', 0)
        ->set('voiceLongitude', 0)
        ->call('searchNearbyByVoice');

    $groups = collect($component->get('voiceSearchGroups'))->keyBy('key');

    expect(collect($groups['complete']['cafes'])->pluck('name')->all())->toContain('Cafe Lengkap')
        ->and(collect($groups['partial']['cafes'])->pluck('name')->all())->toContain('Cafe Sebagian')
        ->and(collect($groups['unrecorded']['cafes'])->pluck('name')->all())->toContain('Cafe Belum Tercatat')
        ->and(collect($groups)->pluck('cafes')->flatten(1)->pluck('name')->all())->not->toContain('Cafe Di Atas Anggaran');
});
