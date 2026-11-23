<?php

namespace Tests\Feature;

use App\Livewire\ExploreSearch;
use App\Models\Cafe;
use App\Models\CafeLocationPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NearestCafeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            $db = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $db->sqliteCreateFunction('acos', 'acos', 1);
            $db->sqliteCreateFunction('cos', 'cos', 1);
            $db->sqliteCreateFunction('sin', 'sin', 1);
            $db->sqliteCreateFunction('radians', fn ($deg) => deg2rad($deg), 1);
        }
    }

    public function test_it_sorts_cafes_by_distance_when_nearest_filter_is_active()
    {
        // 1. Create Cafes at known locations
        // User Location: 0, 0 for simplicity

        // Cafe A: 0.15 degree latitude away (~16.6km)
        $cafeFar = Cafe::factory()->create([
            'name' => 'Far Cafe',
            'latitude' => 0.15,
            'longitude' => 0.0,
            'status' => 'published',
        ]);

        // Cafe B: 0.1 degree latitude away (~11km)
        $cafeNear = Cafe::factory()->create([
            'name' => 'Near Cafe',
            'latitude' => 0.1,
            'longitude' => 0.0,
            'status' => 'published',
        ]);

        Cafe::factory()->create([
            'name' => 'Outside Search Radius Cafe',
            'latitude' => 0.25,
            'longitude' => 0.0,
            'status' => 'published',
        ]);

        // 2. Test Livewire Component
        Livewire::test(ExploreSearch::class)
            ->set('userLat', 0.0)
            ->set('userLng', 0.0)
            ->set('filter', 'terdekat')
            ->assertViewHas('cafes', function ($cafes) use ($cafeNear, $cafeFar) {
                $items = $cafes->items();

                // Assert Sort Order: Near First
                return count($items) === 2
                    && $items[0]->id === $cafeNear->id
                    && $items[1]->id === $cafeFar->id;
            });
    }

    public function test_it_calculates_distance_correctly()
    {
        $cafe = Cafe::factory()->create([
            'latitude' => 0.1, // ~11.1 km from 0,0
            'longitude' => 0.0,
            'status' => 'published',
        ]);

        Livewire::test(ExploreSearch::class)
            ->set('userLat', 0.0)
            ->set('userLng', 0.0)
            ->set('filter', 'terdekat')
            ->assertViewHas('cafes', function ($cafes) {
                $distance = $cafes->first()->distance;

                // 1 degree lat is approx 111km. 0.1 is ~11.1km.
                // Allow some margin for float math
                return $distance > 11.0 && $distance < 11.2;
            });
    }

    public function test_it_marks_the_cafe_at_the_current_location_and_orders_other_cafes_after_it(): void
    {
        $currentCafe = Cafe::factory()->create([
            'name' => 'Kedai AK',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 'published',
        ]);
        $nearbyCafe = Cafe::factory()->create([
            'name' => 'Kedai Terdekat',
            'latitude' => 0.002,
            'longitude' => 0,
            'status' => 'published',
        ]);
        $fartherCafe = Cafe::factory()->create([
            'name' => 'Kedai Berikutnya',
            'latitude' => 0.01,
            'longitude' => 0,
            'status' => 'published',
        ]);

        Livewire::test(ExploreSearch::class)
            ->call('setUserLocation', 0.0, 0.0, 5.0)
            ->assertViewHas('nearestCafe', fn ($nearestCafe) => $nearestCafe->is($currentCafe))
            ->assertViewHas('isAtCafe', true)
            ->assertViewHas('cafes', fn ($cafes) => array_map(
                fn ($cafe) => $cafe->id,
                $cafes->items()
            ) === [$currentCafe->id, $nearbyCafe->id, $fartherCafe->id])
            ->assertSee('Lokasi Anda terdeteksi di sekitar')
            ->assertSee('Lokasi Anda');
    }

    public function test_it_identifies_the_registered_location_point_and_shows_gps_coordinates(): void
    {
        $locationPoint = CafeLocationPoint::create([
            'name' => 'AK Kreatif',
            'address' => 'Jl. KS Tubun Dalam No.38B, Dadi Mulya, Samarinda',
            'slug' => 'ak-kreatif',
            'latitude' => -0.490124,
            'longitude' => 117.1399871,
        ]);
        $nearbyCafe = Cafe::factory()->create([
            'name' => 'Raya Caffe',
            'latitude' => -0.4905348,
            'longitude' => 117.1399597,
            'status' => 'published',
        ]);

        Livewire::test(ExploreSearch::class)
            ->call('setUserLocation', -0.490124, 117.1399871, 5.0)
            ->assertViewHas('nearestLocationPoint', fn ($point) => $point->is($locationPoint))
            ->assertViewHas('nearestLocationPointIsClosest', true)
            ->assertViewHas('nearestCafe', fn ($cafe) => $cafe->is($nearbyCafe))
            ->assertViewHas('isAtCafe', true)
            ->assertSee('data-location-point="ak-kreatif"', false)
            ->assertSee('Titik lokasi perangkat Anda')
            ->assertSee('Koordinat: -0.490124, 117.139987')
            ->assertSee('AK Kreatif')
            ->assertSee('Jl. KS Tubun Dalam No.38B')
            ->assertSee('Koordinat pin: -0.4901240, 117.1399871')
            ->assertSee('Pin lokasi terdaftar')
            ->assertSee('Kafe berikutnya')
            ->assertSee('Buka titik GPS di Google Maps');
    }

    public function test_it_does_not_claim_a_cafe_match_when_gps_accuracy_is_low(): void
    {
        CafeLocationPoint::create([
            'name' => 'AK Kreatif',
            'address' => 'Jl. KS Tubun Dalam No.38B, Dadi Mulya, Samarinda',
            'slug' => 'ak-kreatif',
            'latitude' => -0.490124,
            'longitude' => 117.1399871,
        ]);
        Cafe::factory()->create([
            'name' => 'Raya Caffe',
            'latitude' => -0.4905348,
            'longitude' => 117.1399597,
            'status' => 'published',
        ]);

        Livewire::test(ExploreSearch::class)
            ->call('setUserLocation', -0.490124, 117.1399871, 120.0)
            ->assertViewHas('isAtCafe', false)
            ->assertSee('Lokasi perangkat belum cukup presisi untuk memastikan kafe tempat Anda berada')
            ->assertSee('Titik terdaftar terdekat:')
            ->assertSee('AK Kreatif')
            ->assertSee('data-location-point="ak-kreatif"', false)
            ->assertDontSee('Lokasi Anda terdeteksi di sekitar');
    }

    public function test_it_labels_a_nearby_cafe_as_the_closest_point_when_the_user_is_not_at_a_cafe(): void
    {
        $nearestCafe = Cafe::factory()->create([
            'name' => 'Kedai Paling Dekat',
            'latitude' => 0.005,
            'longitude' => 0,
            'status' => 'published',
        ]);

        Livewire::test(ExploreSearch::class)
            ->call('setUserLocation', 0.0, 0.0, 5.0)
            ->assertViewHas('nearestCafe', fn ($cafe) => $cafe->is($nearestCafe))
            ->assertViewHas('isAtCafe', false)
            ->assertSee('Titik kafe terdekat dari lokasi Anda adalah')
            ->assertSee('Kafe terdekat');
    }
}
