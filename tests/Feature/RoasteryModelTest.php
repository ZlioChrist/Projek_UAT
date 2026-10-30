<?php

use App\Models\City;
use App\Models\Roastery;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->city = City::factory()->create();
    $this->user = User::factory()->create(['role' => 'roastery']);
});

// --- WhatsApp Mutator ---

it('normalizes 08xxx to 628xxx for roastery', function () {
    $roastery = Roastery::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'whatsapp_number' => '081234567890',
    ]);

    expect($roastery->whatsapp_number)->toBe('6281234567890');
});

it('sets null for empty whatsapp on roastery', function () {
    $roastery = Roastery::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'whatsapp_number' => '',
    ]);

    expect($roastery->whatsapp_number)->toBeNull();
});

// --- Slug ---

it('auto-generates slug for roastery', function () {
    $roastery = Roastery::factory()->create([
        'name' => 'Biji Kopi Nusantara',
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
    ]);

    expect($roastery->slug)->toBe('biji-kopi-nusantara');
});

it('generates unique slug for duplicate roastery name', function () {
    Roastery::factory()->create([
        'name' => 'Coffee Lab',
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
    ]);

    $r2 = Roastery::factory()->create([
        'name' => 'Coffee Lab',
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
    ]);

    expect($r2->slug)->toBe('coffee-lab-1');
});

it('uses slug as route key for roastery', function () {
    expect((new Roastery)->getRouteKeyName())->toBe('slug');
});

// --- Operating Hours Sync ---

it('syncs operating_hours to dedicated columns on save', function () {
    $roastery = Roastery::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '17:00'],
            'weekend' => ['open' => '10:00', 'close' => '22:00'],
        ],
    ]);

    expect($roastery->weekday_open)->toBe('08:00');
    expect($roastery->weekday_close)->toBe('17:00');
    expect($roastery->weekend_open)->toBe('10:00');
    expect($roastery->weekend_close)->toBe('22:00');
});

// --- is_open Attribute ---

it('identifies 24-hour roastery as always open', function () {
    $roastery = Roastery::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'is_24_hours' => true,
    ]);

    expect($roastery->is_open)->toBeTrue();
});

it('identifies closed roastery on weekday', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(23, 0));

    $roastery = Roastery::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '20:00'],
            'weekend' => ['open' => '10:00', 'close' => '23:00'],
        ],
    ]);

    expect($roastery->is_open)->toBeFalse();
});

it('identifies open roastery on weekend', function () {
    Carbon::setTestNow(Carbon::parse('next saturday')->setTime(15, 0));

    $roastery = Roastery::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '20:00'],
            'weekend' => ['open' => '10:00', 'close' => '23:00'],
        ],
    ]);

    expect($roastery->is_open)->toBeTrue();
});

// --- Image Processing ---

it('processes roastery images with storage prefix', function () {
    $roastery = Roastery::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'image_path' => 'roasteries/main.jpg',
        'images' => ['roasteries/alt.jpg'],
    ]);

    $processed = $roastery->processed_images;
    expect($processed[0])->toBe('/storage/roasteries/main.jpg');
});

it('returns fallback image when roastery has no images', function () {
    $roastery = Roastery::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'image_path' => null,
        'images' => null,
    ]);

    $processed = $roastery->processed_images;
    expect($processed)->not->toBeEmpty();
    expect($processed[0])->toContain('unsplash.com');
});
