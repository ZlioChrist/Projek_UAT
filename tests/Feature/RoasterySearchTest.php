<?php

use App\Livewire\RoasterySearch;
use App\Models\City;
use App\Models\Roastery;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

// --- Published Filter ---

it('only renders published roasteries', function () {
    Roastery::factory()->create(['name' => 'Published Roastery', 'status' => 'published']);
    Roastery::factory()->create(['name' => 'Draft Roastery', 'status' => 'draft']);

    Livewire::test(RoasterySearch::class)
        ->assertSee('Published Roastery')
        ->assertDontSee('Draft Roastery');
});

// --- Search ---

it('searches roasteries by name', function () {
    Roastery::factory()->create(['name' => 'Biji Kopi Samarinda', 'status' => 'published']);
    Roastery::factory()->create(['name' => 'Warung Biasa', 'status' => 'published']);

    Livewire::test(RoasterySearch::class)
        ->set('search', 'Biji Kopi')
        ->assertSee('Biji Kopi Samarinda')
        ->assertDontSee('Warung Biasa');
});

it('validates roastery search max length', function () {
    Livewire::test(RoasterySearch::class)
        ->set('search', str_repeat('x', 101))
        ->assertHasErrors(['search' => 'max']);
});

// --- City Filter ---

it('filters roasteries by city', function () {
    $city = City::factory()->create();
    $otherCity = City::factory()->create();

    Roastery::factory()->create(['name' => 'My Roastery', 'city_id' => $city->id, 'status' => 'published']);
    Roastery::factory()->create(['name' => 'Other Roastery', 'city_id' => $otherCity->id, 'status' => 'published']);

    Livewire::test(RoasterySearch::class)
        ->set('cityId', $city->id)
        ->assertSee('My Roastery')
        ->assertDontSee('Other Roastery');
});

// --- Letter Filter ---

it('filters roasteries by first letter', function () {
    Roastery::factory()->create(['name' => 'ABC Roasters', 'status' => 'published']);
    Roastery::factory()->create(['name' => 'XYZ Beans', 'status' => 'published']);

    Livewire::test(RoasterySearch::class)
        ->call('setLetter', 'A')
        ->assertSee('ABC Roasters')
        ->assertDontSee('XYZ Beans');
});

// --- Sorting ---

it('sorts roasteries A-Z', function () {
    Roastery::factory()->create(['name' => 'Zeta Roasters', 'status' => 'published']);
    Roastery::factory()->create(['name' => 'Alpha Beans', 'status' => 'published']);

    Livewire::test(RoasterySearch::class)
        ->call('setSort', 'name_az')
        ->assertSeeInOrder(['Alpha Beans', 'Zeta Roasters']);
});

// --- Load More ---

it('increases perPage for roastery search', function () {
    Livewire::test(RoasterySearch::class)
        ->assertSet('perPage', 12)
        ->call('loadMore')
        ->assertSet('perPage', 24);
});

it('caps roastery perPage at 120', function () {
    Livewire::test(RoasterySearch::class)
        ->set('perPage', 120)
        ->call('loadMore')
        ->assertSet('perPage', 120);
});

// --- Reset ---

it('resets all roastery filters', function () {
    Livewire::test(RoasterySearch::class)
        ->set('search', 'test')
        ->set('filter', 'buka')
        ->set('sort', 'name_az')
        ->call('resetAllFilters')
        ->assertSet('search', '')
        ->assertSet('filter', 'semua')
        ->assertSet('sort', 'relevance')
        ->assertSet('activeLetter', null)
        ->assertSet('cityId', null)
        ->assertSet('perPage', 12);
});

// --- Open Now Filter ---

it('filters only open roasteries', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(10, 0));

    Roastery::factory()->create([
        'name' => 'Open Roastery',
        'status' => 'published',
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '22:00'],
        ],
    ]);

    Roastery::factory()->create([
        'name' => 'Closed Roastery',
        'status' => 'published',
        'operating_hours' => [
            'weekday' => ['open' => '18:00', 'close' => '23:00'],
        ],
    ]);

    Livewire::test(RoasterySearch::class)
        ->set('filter', 'buka')
        ->assertSee('Open Roastery')
        ->assertDontSee('Closed Roastery');
});

it('filters by a fresh official roastery status when available', function () {
    Roastery::factory()->create([
        'name' => 'Official Open Roastery',
        'status' => 'published',
        'official_is_open' => true,
        'official_status_checked_at' => now(),
        'operating_hours' => ['weekday' => ['open' => '18:00', 'close' => '20:00']],
    ]);
    Roastery::factory()->create([
        'name' => 'Official Closed Roastery',
        'status' => 'published',
        'official_is_open' => false,
        'official_status_checked_at' => now(),
        'operating_hours' => ['weekday' => ['open' => '08:00', 'close' => '22:00']],
    ]);

    Livewire::test(RoasterySearch::class)
        ->set('filter', 'buka')
        ->assertSee('Official Open Roastery')
        ->assertDontSee('Official Closed Roastery');
});

it('shows the fresh official status and next-time label on roastery cards', function () {
    Roastery::factory()->create([
        'name' => 'Official Roastery',
        'status' => 'published',
        'official_is_open' => false,
        'official_status_text' => 'Buka 16:00',
        'official_status_checked_at' => now(),
    ]);

    Livewire::test(RoasterySearch::class)
        ->assertSee('Tutup')
        ->assertSee('Buka 16:00');
});

it('shows scheduled opening and closing times when a fresh official status has no time label', function () {
    Roastery::factory()->create([
        'name' => 'Official Closed Roastery',
        'status' => 'published',
        'official_is_open' => false,
        'official_status_text' => null,
        'official_status_checked_at' => now(),
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '22:00'],
            'weekend' => ['open' => '08:00', 'close' => '22:00'],
        ],
    ]);

    Livewire::test(RoasterySearch::class)
        ->assertSee('Tutup')
        ->assertSee('Buka 08:00');
});

// --- User Location ---

it('sets user location for roastery search', function () {
    Livewire::test(RoasterySearch::class)
        ->call('setUserLocation', -1.25, 116.85)
        ->assertSet('userLat', -1.25)
        ->assertSet('userLng', 116.85)
        ->assertSet('filter', 'terdekat')
        ->assertSet('sort', 'distance');
});

it('explains when nearest roastery results cannot be calculated without roastery coordinates', function () {
    Roastery::factory()->create(['status' => 'published']);

    Livewire::test(RoasterySearch::class)
        ->call('setUserLocation', -1.25, 116.85)
        ->assertSee('Lokasi Roastery Belum Tersedia')
        ->assertSee('latitude dan longitude roastery');
});
