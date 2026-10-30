<?php

use App\Models\Cafe;
use App\Models\Roastery;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('syncs missing cafe and roastery coordinates from official Google Maps links', function () {
    $cafe = Cafe::factory()->create([
        'name' => 'Mapped Cafe',
        'slug' => 'mapped-cafe',
        'status' => 'published',
        'google_maps_url' => 'https://maps.app.goo.gl/cafe',
    ]);
    $roastery = Roastery::factory()->create([
        'name' => 'Mapped Roastery',
        'slug' => 'mapped-roastery',
        'status' => 'published',
        'google_maps_url' => 'https://maps.app.goo.gl/roastery',
    ]);

    Http::fake(function (Request $request) {
        return match ($request->url()) {
            'https://maps.app.goo.gl/cafe' => Http::response('', 200, [
                'Location' => 'https://www.google.com/maps/place/Cafe/@-0.5036212,117.1507308,17z',
            ]),
            'https://maps.app.goo.gl/roastery' => Http::response('', 200, [
                'Location' => 'https://www.google.com/maps/place/Roastery/data=!3d-0.51!4d117.16',
            ]),
            default => Http::response('', 404),
        };
    });

    $this->artisan('app:sync-official-place-coordinates')
        ->expectsOutput('Updated coordinates for 2 published places.')
        ->assertExitCode(0);

    expect((float) $cafe->fresh()->latitude)->toBe(-0.5036212)
        ->and((float) $cafe->fresh()->longitude)->toBe(117.1507308)
        ->and((float) $roastery->fresh()->latitude)->toBe(-0.51)
        ->and((float) $roastery->fresh()->longitude)->toBe(117.16);
});

it('does not request untrusted map URLs or overwrite existing coordinates', function () {
    $untrusted = Cafe::factory()->create([
        'slug' => 'untrusted-map',
        'status' => 'published',
        'google_maps_url' => 'https://example.com/@-0.5,117.1',
    ]);
    $existing = Cafe::factory()->create([
        'slug' => 'existing-coordinates',
        'status' => 'published',
        'google_maps_url' => 'https://maps.app.goo.gl/existing',
        'latitude' => -0.5,
        'longitude' => 117.1,
    ]);

    Http::fake();

    $this->artisan('app:sync-official-place-coordinates')
        ->expectsOutputToContain('The saved map link is not a trusted Google Maps URL.')
        ->assertExitCode(1);

    Http::assertSentCount(0);
    expect($untrusted->fresh()->latitude)->toBeNull()
        ->and((float) $existing->fresh()->latitude)->toBe(-0.5)
        ->and((float) $existing->fresh()->longitude)->toBe(117.1);
});
