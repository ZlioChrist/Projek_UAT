<?php

use App\Models\Cafe;
use App\Models\Information;
use App\Models\Roastery;
use Illuminate\Support\Carbon;

// --- Null Operating Hours ---

it('handles cafe with null operating hours gracefully', function () {
    $cafe = Cafe::factory()->create([
        'operating_hours' => null,
        'is_24_hours' => false,
        'status' => 'published',
    ]);

    expect($cafe->today_hours)->toBeNull();
    expect($cafe->is_open)->toBeFalse();
});

// --- Empty Images ---

it('handles cafe with empty images array', function () {
    $cafe = Cafe::factory()->create([
        'image_path' => null,
        'images' => [],
        'status' => 'published',
    ]);

    $processed = $cafe->processed_images;
    expect($processed)->not->toBeEmpty();
    expect($processed[0])->toContain('unsplash.com');
});

// --- Special Characters in Names ---

it('handles special characters in cafe name', function () {
    $cafe = Cafe::factory()->create([
        'name' => "Café D'Or & Co. (Branch #1)",
        'status' => 'published',
    ]);

    $cafe->refresh();
    expect($cafe->name)->toBe("Café D'Or & Co. (Branch #1)");
    expect($cafe->slug)->not->toBeEmpty();
});

it('generates valid slug from unicode name', function () {
    $cafe = Cafe::factory()->create([
        'name' => 'Kedai Köpi Ünit',
        'status' => 'published',
    ]);

    expect($cafe->slug)->not->toBeEmpty();
    expect($cafe->slug)->not->toContain(' ');
});

// --- Unpublished Access ---

it('returns 404 for unpublished cafe via route', function () {
    $cafe = Cafe::factory()->create(['status' => 'draft']);

    $this->get(route('cafes.show', $cafe))->assertNotFound();
});

it('returns 404 for review status cafe', function () {
    $cafe = Cafe::factory()->create(['status' => 'review']);

    $this->get(route('cafes.show', $cafe))->assertNotFound();
});

it('returns 404 for unpublished roastery via route', function () {
    $roastery = Roastery::factory()->create(['status' => 'draft']);

    $this->get(route('roastery.show', $roastery))->assertNotFound();
});

// --- Overnight Operating Hours ---

it('detects cafe as open during overnight hours', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(1, 0)); // 1 AM

    $cafe = Cafe::factory()->create([
        'operating_hours' => [
            'weekday' => ['open' => '22:00', 'close' => '04:00'],
        ],
        'status' => 'published',
    ]);

    expect($cafe->is_open)->toBeTrue();
});

it('detects cafe as closed before overnight opening', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(20, 0)); // 8 PM

    $cafe = Cafe::factory()->create([
        'operating_hours' => [
            'weekday' => ['open' => '22:00', 'close' => '04:00'],
        ],
        'status' => 'published',
    ]);

    expect($cafe->is_open)->toBeFalse();
});

// --- 24 Hour Cafe ---

it('marks 24-hour cafe as always open', function () {
    Carbon::setTestNow(Carbon::parse('next wednesday')->setTime(3, 30));

    $cafe = Cafe::factory()->create([
        'is_24_hours' => true,
        'status' => 'published',
    ]);

    expect($cafe->is_open)->toBeTrue();
});

// --- Information Edge Cases ---

it('returns 404 when accessing unpublished information article', function () {
    $info = Information::factory()->create(['is_published' => false]);

    $this->get(route('information.show', $info))->assertNotFound();
});

// --- Empty Database ---

it('displays explore page with no cafes', function () {
    $this->get(route('explore'))->assertSuccessful();
});

it('displays roastery page with no roasteries', function () {
    $this->get(route('roastery'))->assertSuccessful();
});

it('displays information page with no articles', function () {
    $this->get(route('information'))->assertSuccessful();
});

// --- Roastery Model Edge Cases ---

it('handles roastery with null operating hours', function () {
    $roastery = Roastery::factory()->create([
        'operating_hours' => null,
        'is_24_hours' => false,
        'status' => 'published',
    ]);

    expect($roastery->today_hours)->toBeNull();
    expect($roastery->is_open)->toBeFalse();
});

it('auto-generates unique slug for roastery', function () {
    Roastery::factory()->create(['name' => 'Kopi Origins']);
    $r2 = Roastery::factory()->create(['name' => 'Kopi Origins']);

    expect($r2->slug)->not->toBe('kopi-origins');
    expect($r2->slug)->toStartWith('kopi-origins');
});
