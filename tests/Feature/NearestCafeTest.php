<?php

namespace Tests\Feature;

use App\Livewire\ExploreSearch;
use App\Models\Cafe;
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

    /** @test */
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

    /** @test */
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
}
