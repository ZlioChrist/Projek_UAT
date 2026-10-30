<?php

use App\Livewire\CafeDetail;
use App\Models\Cafe;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('mounts with published cafe and sets hasCafe to true', function () {
    $cafe = Cafe::factory()->create(['status' => 'published', 'is_24_hours' => true]);

    Livewire::test(CafeDetail::class, ['cafeId' => $cafe->id])
        ->assertSet('hasCafe', true)
        ->assertSet('isOpen', true);
});

it('mounts with draft cafe and sets hasCafe to false', function () {
    $cafe = Cafe::factory()->create(['status' => 'draft']);

    Livewire::test(CafeDetail::class, ['cafeId' => $cafe->id])
        ->assertSet('hasCafe', false)
        ->assertSet('isOpen', false);
});

it('refreshes status correctly', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(10, 0));

    $cafe = Cafe::factory()->create([
        'status' => 'published',
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '22:00'],
        ],
    ]);

    Livewire::test(CafeDetail::class, ['cafeId' => $cafe->id])
        ->assertSet('isOpen', true)
        ->call('refreshStatus')
        ->assertSet('isOpen', true);
});

it('shows closed when cafe is outside operating hours', function () {
    Carbon::setTestNow(Carbon::parse('next monday')->setTime(23, 30));

    $cafe = Cafe::factory()->create([
        'status' => 'published',
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '22:00'],
        ],
    ]);

    Livewire::test(CafeDetail::class, ['cafeId' => $cafe->id])
        ->assertSet('isOpen', false);
});

it('handles nonexistent cafe id gracefully', function () {
    Livewire::test(CafeDetail::class, ['cafeId' => 99999])
        ->assertSet('hasCafe', false)
        ->assertSet('isOpen', false);
});
