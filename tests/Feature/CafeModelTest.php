<?php

use App\Models\Cafe;
use App\Models\City;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->city = City::factory()->create();
    $this->user = User::factory()->create(['role' => 'admin']);
});

// --- WhatsApp Mutator ---

it('normalizes 08xxx to 628xxx', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'whatsapp_number' => '081234567890',
    ]);

    expect($cafe->whatsapp_number)->toBe('6281234567890');
});

it('normalizes number without 62 prefix', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'whatsapp_number' => '81234567890',
    ]);

    expect($cafe->whatsapp_number)->toBe('6281234567890');
});

it('keeps number already with 62 prefix', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'whatsapp_number' => '6281234567890',
    ]);

    expect($cafe->whatsapp_number)->toBe('6281234567890');
});

it('strips non-numeric characters from whatsapp', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'whatsapp_number' => '+62 812-3456-7890',
    ]);

    expect($cafe->whatsapp_number)->toBe('6281234567890');
});

it('sets null for empty whatsapp number', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'whatsapp_number' => '',
    ]);

    expect($cafe->whatsapp_number)->toBeNull();
});

// --- Slug Generation ---

it('auto-generates slug on create', function () {
    $cafe = Cafe::factory()->create([
        'name' => 'Kopi Kenangan Samarinda',
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
    ]);

    expect($cafe->slug)->toBe('kopi-kenangan-samarinda');
});

it('generates unique slug when duplicate name exists', function () {
    Cafe::factory()->create([
        'name' => 'Kopi Kenangan',
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
    ]);

    $cafe2 = Cafe::factory()->create([
        'name' => 'Kopi Kenangan',
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
    ]);

    expect($cafe2->slug)->toBe('kopi-kenangan-1');
});

it('updates slug when name changes', function () {
    $cafe = Cafe::factory()->create([
        'name' => 'Old Name',
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
    ]);

    $cafe->update(['name' => 'New Cafe Name']);
    $cafe->refresh();

    expect($cafe->slug)->toBe('new-cafe-name');
});

it('uses slug as route key', function () {
    $cafe = new Cafe;
    expect($cafe->getRouteKeyName())->toBe('slug');
});

// --- Image Processing ---

it('processes images correctly with storage prefix', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'image_path' => 'cafes/photo.jpg',
        'images' => ['cafes/photo2.jpg'],
    ]);

    $processed = $cafe->processed_images;

    expect($processed[0])->toBe('/storage/cafes/photo.jpg');
    expect($processed[1])->toBe('/storage/cafes/photo2.jpg');
});

it('keeps http urls as-is in images', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'image_path' => 'https://example.com/photo.jpg',
        'images' => [],
    ]);

    $processed = $cafe->processed_images;
    expect($processed[0])->toBe('https://example.com/photo.jpg');
});

it('returns fallback image when no images exist', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'image_path' => null,
        'images' => null,
    ]);

    $processed = $cafe->processed_images;
    expect($processed)->not->toBeEmpty();
    expect($processed[0])->toContain('unsplash.com');
});

// --- Operating Hours Validation ---

it('normalizes incomplete operating hours on save', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '08:00'], // Missing 'close'
        ],
    ]);

    // Incomplete entry should be removed
    expect($cafe->operating_hours)->not->toHaveKey('weekday');
});

it('normalizes 24:00 to 00:00 in operating hours', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '24:00', 'close' => '24:00'],
        ],
    ]);

    expect($cafe->operating_hours['weekday']['open'])->toBe('00:00');
    expect($cafe->operating_hours['weekday']['close'])->toBe('00:00');
});

// --- today_hours Accessor ---

it('returns 24 Jam label for 24-hour cafes', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'is_24_hours' => true,
    ]);

    expect($cafe->today_hours['label'])->toBe('24 Jam');
});

it('returns correct weekday hours', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(10, 0));

    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '20:00'],
            'weekend' => ['open' => '10:00', 'close' => '23:00'],
        ],
    ]);

    $hours = $cafe->today_hours;
    expect($hours['open'])->toBe('08:00');
    expect($hours['close'])->toBe('20:00');
});

it('returns correct weekend hours', function () {
    Carbon::setTestNow(Carbon::parse('next saturday')->setTime(12, 0));

    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '20:00'],
            'weekend' => ['open' => '10:00', 'close' => '23:00'],
        ],
    ]);

    $hours = $cafe->today_hours;
    expect($hours['open'])->toBe('10:00');
    expect($hours['close'])->toBe('23:00');
});

it('returns null for today_hours when no hours are set', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'is_24_hours' => false,
        'operating_hours' => null,
    ]);

    expect($cafe->today_hours)->toBeNull();
});
