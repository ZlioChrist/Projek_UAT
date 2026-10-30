<?php

use App\Livewire\ExploreSearch;
use App\Models\Cafe;
use App\Models\City;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

// --- Rendering & Published Filter ---

beforeEach(function () {
    Livewire::withoutLazyLoading();
});

it('only renders published cafes', function () {
    $published = Cafe::factory()->create(['name' => 'Published Cafe', 'status' => 'published']);
    $draft = Cafe::factory()->create(['name' => 'Draft Cafe', 'status' => 'draft']);

    Livewire::test(ExploreSearch::class)
        ->assertSee('Published Cafe')
        ->assertDontSee('Draft Cafe');
});

it('shows scheduled opening and closing times when a fresh official status has no time label', function () {
    Cafe::factory()->create([
        'name' => 'Official Closed Cafe',
        'status' => 'published',
        'official_is_open' => false,
        'official_status_text' => null,
        'official_status_checked_at' => now(),
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '22:00'],
            'weekend' => ['open' => '08:00', 'close' => '22:00'],
        ],
    ]);

    Livewire::test(ExploreSearch::class)
        ->assertSee('Tutup')
        ->assertSee('Buka 08:00');
});

// --- Search ---

it('filters cafes by search term', function () {
    Cafe::factory()->create(['name' => 'Kopi Susu Tetangga', 'status' => 'published']);
    Cafe::factory()->create(['name' => 'Warung Makan Enak', 'status' => 'published']);

    Livewire::test(ExploreSearch::class)
        ->set('search', 'Kopi Susu')
        ->assertSee('Kopi Susu Tetangga')
        ->assertDontSee('Warung Makan Enak');
});

it('validates search max length', function () {
    $longSearch = str_repeat('a', 101);

    Livewire::test(ExploreSearch::class)
        ->set('search', $longSearch)
        ->assertHasErrors(['search' => 'max']);
});

// --- City Filter ---

it('filters cafes by city', function () {
    $city1 = City::factory()->create(['name' => 'Samarinda']);
    $city2 = City::factory()->create(['name' => 'Balikpapan']);

    Cafe::factory()->create(['name' => 'Cafe Samarinda', 'city_id' => $city1->id, 'status' => 'published']);
    Cafe::factory()->create(['name' => 'Cafe Balikpapan', 'city_id' => $city2->id, 'status' => 'published']);

    Livewire::test(ExploreSearch::class)
        ->set('cityId', $city1->id)
        ->assertSee('Cafe Samarinda')
        ->assertDontSee('Cafe Balikpapan');
});

// --- Letter Filter ---

it('filters cafes by first letter', function () {
    Cafe::factory()->create(['name' => 'Alpha Coffee', 'status' => 'published']);
    Cafe::factory()->create(['name' => 'Beta Brew', 'status' => 'published']);

    Livewire::test(ExploreSearch::class)
        ->call('setLetter', 'A')
        ->assertSee('Alpha Coffee')
        ->assertDontSee('Beta Brew');
});

it('toggles letter filter off when same letter clicked', function () {
    Cafe::factory()->create(['name' => 'Alpha Coffee', 'status' => 'published']);
    Cafe::factory()->create(['name' => 'Beta Brew', 'status' => 'published']);

    Livewire::test(ExploreSearch::class)
        ->call('setLetter', 'A')
        ->assertSet('activeLetter', 'A')
        ->call('setLetter', 'A')
        ->assertSet('activeLetter', null)
        ->assertSee('Alpha Coffee')
        ->assertSee('Beta Brew');
});

// --- Sorting ---

it('sorts cafes A-Z', function () {
    Cafe::factory()->create(['name' => 'Zebra Coffee', 'status' => 'published']);
    Cafe::factory()->create(['name' => 'Alpha Brew', 'status' => 'published']);

    Livewire::test(ExploreSearch::class)
        ->call('setSort', 'name_az')
        ->assertSeeInOrder(['Alpha Brew', 'Zebra Coffee']);
});

it('sorts cafes Z-A', function () {
    Cafe::factory()->create(['name' => 'Alpha Brew', 'status' => 'published']);
    Cafe::factory()->create(['name' => 'Zebra Coffee', 'status' => 'published']);

    Livewire::test(ExploreSearch::class)
        ->call('setSort', 'name_za')
        ->assertSeeInOrder(['Zebra Coffee', 'Alpha Brew']);
});

// --- Load More ---

it('increases perPage when loading more', function () {
    Livewire::test(ExploreSearch::class)
        ->assertSet('perPage', 12)
        ->call('loadMore')
        ->assertSet('perPage', 24);
});

it('caps perPage at MAX_PER_PAGE', function () {
    Livewire::test(ExploreSearch::class)
        ->set('perPage', 1000)
        ->call('loadMore')
        ->assertSet('perPage', 1000);
});

// --- Reset Filters ---

it('resets all filters to defaults', function () {
    Livewire::test(ExploreSearch::class)
        ->set('search', 'test')
        ->set('filter', 'buka')
        ->set('sort', 'name_az')
        ->set('activeLetter', 'A')
        ->call('resetAllFilters')
        ->assertSet('search', '')
        ->assertSet('filter', 'semua')
        ->assertSet('sort', 'relevance')
        ->assertSet('activeLetter', null)
        ->assertSet('cityId', null)
        ->assertSet('perPage', 12);
});

// --- Open Now Filter ---

it('filters only open cafes when buka filter is active', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(10, 0));

    $openCafe = Cafe::factory()->create([
        'name' => 'Open Cafe',
        'status' => 'published',
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '22:00'],
        ],
    ]);

    $closedCafe = Cafe::factory()->create([
        'name' => 'Closed Cafe',
        'status' => 'published',
        'operating_hours' => [
            'weekday' => ['open' => '18:00', 'close' => '23:00'],
        ],
    ]);

    Livewire::test(ExploreSearch::class)
        ->set('filter', 'buka')
        ->assertSee('Open Cafe')
        ->assertDontSee('Closed Cafe');
});

// --- User Location ---

it('accepts user location coordinates', function () {
    Livewire::test(ExploreSearch::class)
        ->call('setUserLocation', -1.25, 116.85)
        ->assertSet('userLat', -1.25)
        ->assertSet('userLng', 116.85)
        ->assertSet('filter', 'terdekat')
        ->assertSet('sort', 'distance');
});

it('explains when nearest cafe results cannot be calculated without cafe coordinates', function () {
    Cafe::factory()->create(['status' => 'published']);

    Livewire::test(ExploreSearch::class)
        ->call('setUserLocation', -1.25, 116.85)
        ->assertSee('Lokasi Cafe Belum Tersedia')
        ->assertSee('latitude dan longitude cafe');
});
