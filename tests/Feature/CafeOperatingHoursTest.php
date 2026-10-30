<?php

use App\Models\Cafe;
use App\Models\City;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->city = City::create(['name' => 'Banjarmasin', 'slug' => 'banjarmasin']);
    $this->user = User::factory()->create(['role' => 'admin']);
});

it('identifies a 24-hour cafe as always open', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'is_24_hours' => true,
        'latitude' => -3.316694,
        'longitude' => 114.590111,
    ]);

    expect($cafe->is_open)->toBeTrue();

    // Check at late night
    Carbon::setTestNow(now()->setTime(23, 59));
    expect($cafe->is_open)->toBeTrue();
});

it('correctly handles weekday vs weekend hours', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '20:00'],
            'weekend' => ['open' => '10:00', 'close' => '23:00'],
        ],
        'latitude' => -3.316694,
        'longitude' => 114.590111,
    ]);

    // Monday (Weekday) at 9:00 AM - Should be open
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(9, 0));
    expect($cafe->is_open)->toBeTrue();

    // Monday at 9:00 PM - Should be closed (weekday closes at 20:00)
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(21, 0));
    expect($cafe->is_open)->toBeFalse();

    // Saturday (Weekend) at 9:00 PM - Should be open (weekend closes at 23:00)
    Carbon::setTestNow(Carbon::parse('next saturday')->setTime(21, 0));
    expect($cafe->is_open)->toBeTrue();
});

it('handles overnight hours in flexible schedule', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '18:00', 'close' => '02:00'],
            'weekend' => ['open' => '18:00', 'close' => '04:00'],
        ],
        'latitude' => -3.316694,
        'longitude' => 114.590111,
    ]);

    // Tuesday at 1:00 AM - Should be open (weekday closes at 02:00)
    Carbon::setTestNow(Carbon::parse('next tuesday')->setTime(1, 0));
    expect($cafe->is_open)->toBeTrue();

    // Tuesday at 3:00 AM - Should be closed (weekday closes at 02:00)
    Carbon::setTestNow(Carbon::parse('next tuesday')->setTime(3, 0));
    expect($cafe->is_open)->toBeFalse();
});

it('uses the specific weekday schedule when daily hours are available', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-26 18:00:00', 'Asia/Makassar'));

    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'monday' => ['open' => '16:00', 'close' => '01:00'],
            'tuesday' => null,
        ],
    ]);

    expect($cafe->today_hours)->toBe(['open' => '16:00', 'close' => '01:00'])
        ->and($cafe->is_open)->toBeTrue()
        ->and(Cafe::openNow()->whereKey($cafe->id)->exists())->toBeTrue()
        ->and($cafe->weekday_open)->toBeNull();
});

it('respects a daily closed entry instead of falling back to weekday hours', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-27 12:00:00', 'Asia/Makassar'));

    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '20:00'],
            'tuesday' => null,
        ],
    ]);

    expect($cafe->today_hours)->toBeNull()
        ->and($cafe->is_open)->toBeFalse()
        ->and(Cafe::openNow()->whereKey($cafe->id)->exists())->toBeFalse();
});

it('uses the previous day close for after-midnight daily schedules', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-27 01:30:00', 'Asia/Makassar'));

    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'operating_hours' => [
            'monday' => ['open' => '16:00', 'close' => '01:00'],
            'tuesday' => ['open' => '18:00', 'close' => '02:00'],
        ],
    ]);

    expect($cafe->is_open)->toBeFalse()
        ->and(Cafe::openNow()->whereKey($cafe->id)->exists())->toBeFalse();
});

it('falls back to legacy fields if operating_hours is null', function () {
    $cafe = Cafe::factory()->create([
        'city_id' => $this->city->id,
        'owner_id' => $this->user->id,
        'opening_time' => '08:00',
        'closing_time' => '17:00',
        'latitude' => -3.316694,
        'longitude' => 114.590111,
    ]);

    Carbon::setTestNow(now()->setTime(10, 0));
    expect($cafe->is_open)->toBeTrue();

    Carbon::setTestNow(now()->setTime(18, 0));
    expect($cafe->is_open)->toBeFalse();
});
